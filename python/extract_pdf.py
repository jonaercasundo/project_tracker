#!/usr/bin/env python3
"""
CLI entry point called by Laravel's SchoolImportController via subprocess.

Usage:
    python extract_pdf.py <path_or_url>

Reuses the tested extraction logic in extractor.py (native text/table
extraction with OCR fallback for scanned pages), then adds a mapping layer
on top: since government allocation lists use inconsistent column headers
("School ID" vs "Sch. ID" vs "BEIS School ID", etc.), this guesses which
extracted column corresponds to which target field.

Output shape (what the Laravel controller expects):
{
    "status": "success",
    "source": "...",
    "report_title": "...",
    "pages": 3,
    "extraction_summary": {...},
    "column_mapping_used": [{"page": 1, "mapping": {"Sch. ID": "School ID", ...}}],
    "schools": [
        {"School ID": "...", "School Name": "...", "Municipality": "...", "Division": "...", "Region": "..."}
    ]
}

Rows where no column could be confidently mapped are left out of "schools"
entirely rather than guessed wrong -- see column_mapping_used to see exactly
what was matched, and extraction_summary/pages_detail (via the service) for
the raw content if you need to debug why a page produced nothing.
"""

import sys
import os
import re
import json
import difflib
import tempfile

import requests

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from extractor import extract_pdf as extract_pdf_raw


# Fields where a blank cell usually means "merged with the row above"
# (grouping/category data like Region or Division is often written once
# and visually spans several rows in the source PDF). These get
# forward-filled. Fields NOT in this set (School ID, School Name) are never
# forward-filled -- a blank there means the row is genuinely incomplete and
# should surface as-is for human review, not be silently guessed.
MERGE_FILL_FIELDS = {"Municipality", "Division", "Region"}

# Known header variants seen across inconsistent government PDF formats.
# Add new variants here as you encounter them in real documents -- this
# list is meant to grow over time, not be exhaustive on day one.
FIELD_SYNONYMS = {
    "School ID": [
        "school id", "schoolid", "sch id", "sch. id", "id no", "id number",
        "beis school id", "beis id",
    ],
    "School Name": [
        "school name", "name of school", "school",
    ],
    "Municipality": [
        "municipality", "city/municipality", "city municipality", "town", "lgu",
    ],
    "Division": [
        "division", "school division", "division office", "sdo",
    ],
    "Region": [
        "region", "region name",
    ],
}

FUZZY_CUTOFF = 0.6
SUBSTRING_MIN_SCORE = 0.5

# Rows that are actually subtotal/grand-total summary lines, not real data.
# These show up interleaved in the table body in real government PDFs
# (e.g. "TOTAL" per region, "GRAND TOTAL (ALL REGIONS)"). If left in, they
# not only appear as fake schools but -- worse -- their text can get
# forward-filled into every real row below them via MERGE_FILL_FIELDS.
TOTAL_ROW_PATTERN = re.compile(r"\b(grand\s*total|sub-?\s*total|total)\b", re.IGNORECASE)


def looks_like_total_row(raw_row: dict, mapped_school: dict) -> bool:
    """
    A row is treated as a summary/total row (not a real school) when it has
    no identifying data (blank School ID AND blank School Name) AND some
    cell in the row contains "total"/"grand total"/"subtotal" text.
    Rows that DO have a School ID/Name are never treated as total rows,
    even if some other column happens to contain the word "total".
    """
    has_id = bool(mapped_school.get("School ID"))
    has_name = bool(mapped_school.get("School Name"))
    if has_id or has_name:
        return False

    return any(TOTAL_ROW_PATTERN.search(str(v)) for v in raw_row.values() if v)


def normalize(s: str) -> str:
    return re.sub(r"[^a-z0-9]", "", s.lower())


def guess_field(header: str):
    """Return the best-matching target field name for a raw header, or None."""
    norm_header = normalize(header)
    if not norm_header:
        return None

    # Exact match first.
    for field, synonyms in FIELD_SYNONYMS.items():
        for syn in synonyms:
            if normalize(syn) == norm_header:
                return field

    # Substring match, scored by how much of the header the synonym covers.
    best_field = None
    best_score = 0.0
    for field, synonyms in FIELD_SYNONYMS.items():
        for syn in synonyms:
            norm_syn = normalize(syn)
            if norm_syn in norm_header or norm_header in norm_syn:
                score = len(norm_syn) / max(len(norm_header), 1)
                if score > best_score:
                    best_score = score
                    best_field = field

    if best_score >= SUBSTRING_MIN_SCORE:
        return best_field

    # Fuzzy fallback for typos/OCR noise (e.g. "Munlcipallty").
    all_pairs = [(field, syn) for field, syns in FIELD_SYNONYMS.items() for syn in syns]
    choices = [normalize(syn) for _, syn in all_pairs]
    matches = difflib.get_close_matches(norm_header, choices, n=1, cutoff=FUZZY_CUTOFF)
    if matches:
        idx = choices.index(matches[0])
        return all_pairs[idx][0]

    return None


def map_table_to_schools(table: dict):
    """
    Map one extracted table's rows onto target school fields.
    Returns (mapped_rows, header_to_field_mapping).
    """
    headers = table["headers"]

    mapping = {}
    used_fields = set()
    for h in headers:
        field = guess_field(h)
        if field and field not in used_fields:
            mapping[h] = field
            used_fields.add(field)

    schools = []
    last_seen = {}  # tracks the last non-blank value per merge-fill field,
                     # reset for each table so fills never bleed across tables

    for row in table["rows"]:
        school = {field: row.get(h) for h, field in mapping.items()}

        # Skip subtotal/grand-total rows entirely -- before forward-fill,
        # so their text (e.g. "TOTAL" landing in a Division-mapped column)
        # can never leak into last_seen and get propagated into real rows.
        if looks_like_total_row(row, school):
            continue

        # Forward-fill merged grouping cells (e.g. a Region/Division that
        # visually spans several rows but is only present once in the
        # extracted data). Only applies to MERGE_FILL_FIELDS -- identifying
        # fields like School ID/School Name are left blank as-is so an
        # incomplete row surfaces for human review instead of being masked.
        for field in MERGE_FILL_FIELDS:
            if field not in school:
                continue
            value = school[field]
            if value not in (None, ""):
                last_seen[field] = value
            elif field in last_seen:
                school[field] = last_seen[field]

        # Only keep rows where at least one field actually mapped to
        # something non-empty -- an all-blank row from a mapping that
        # matched nothing meaningful isn't useful data.
        if any(v not in (None, "") for v in school.values()):
            schools.append(school)

    return schools, mapping


def download_pdf(url: str) -> str:
    response = requests.get(url, headers={"User-Agent": "Mozilla/5.0"}, timeout=60)
    response.raise_for_status()

    fd, temp_file = tempfile.mkstemp(suffix=".pdf")
    os.close(fd)
    with open(temp_file, "wb") as f:
        f.write(response.content)

    return temp_file


def main(path_or_url: str):
    temp_file = None

    try:
        if path_or_url.startswith("http"):
            pdf_path = download_pdf(path_or_url)
            temp_file = pdf_path
            source_name = path_or_url
        else:
            pdf_path = path_or_url
            source_name = os.path.basename(pdf_path)

        if not os.path.exists(pdf_path):
            raise FileNotFoundError(pdf_path)

        raw = extract_pdf_raw(pdf_path, source_name)

        schools = []
        column_mapping_used = []

        for page in raw["pages_detail"]:
            for table in page["tables"]:
                mapped_rows, mapping = map_table_to_schools(table)
                schools.extend(mapped_rows)
                if mapping:
                    column_mapping_used.append({
                        "page": page["page_number"],
                        "mapping": mapping,
                    })

        result = {
            "status": "success",
            "source": raw["source"],
            "report_title": raw["report_title"],
            "pages": raw["pages"],
            "extraction_summary": raw["extraction_summary"],
            "column_mapping_used": column_mapping_used,
            "schools": schools,
        }

        print(json.dumps(result))

    except Exception as e:
        # Errors go to stderr (not stdout) so Laravel's
        # $process->getErrorOutput() actually receives them -- stdout is
        # reserved for the success-path JSON the controller parses.
        print(json.dumps({"status": "error", "message": str(e)}), file=sys.stderr)
        sys.exit(1)

    finally:
        if temp_file and os.path.exists(temp_file):
            os.remove(temp_file)


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({
            "status": "error",
            "message": "Usage: python extract_pdf.py <path_or_url>",
        }), file=sys.stderr)
        sys.exit(1)

    main(sys.argv[1])