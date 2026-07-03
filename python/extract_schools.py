import sys
import os
import json
import re
import requests
import pdfplumber
import tempfile


def clean_cell(value):
    if value is None:
        return ""
    return re.sub(r'\s+', ' ', str(value)).strip()


def is_valid_school_id(value):
    return bool(re.match(r"^\d{5,}$", clean_cell(value)))


BAD_KEYWORDS = [
    "FUND", "FACILITIES", "CY", "BASIC EDUCATIONAL",
    "FURNITURE", "PROVISION", "ALLOCATION", "PAGE"
]


def is_noise_row(text):
    if not text:
        return True
    text = text.upper()
    return any(k in text for k in BAD_KEYWORDS)


def parse_tables(page, schools):
    """Column order matches the actual PDF header:
    Region | Division | School ID | School Name | Municipality | LD | ...
    """
    tables = page.extract_tables()

    if not tables:
        return False

    for table in tables:
        for row in table:
            if not row or len(row) < 4:
                continue

            region = clean_cell(row[0]) if len(row) > 0 else ""
            division = clean_cell(row[1]) if len(row) > 1 else ""
            school_id = clean_cell(row[2]) if len(row) > 2 else ""
            school_name = clean_cell(row[3]) if len(row) > 3 else ""
            municipality = clean_cell(row[4]) if len(row) > 4 else ""

            if not is_valid_school_id(school_id):
                continue

            if is_noise_row(school_name):
                continue

            schools.append({
                "School ID": school_id,
                "School Name": school_name,
                "Municipality": municipality,
                "Division": division,
                "Region": region
            })
    return True


def parse_words(page, schools):
    words = page.extract_words()

    lines = {}
    for w in words:
        top = round(w["top"])
        lines.setdefault(top, []).append(w)

    for _, line_words in lines.items():
        line_words = sorted(line_words, key=lambda w: w["x0"])
        text = " ".join(w["text"] for w in line_words)

        if is_noise_row(text):
            continue

        school_id = None
        for w in line_words:
            if is_valid_school_id(w["text"]):
                school_id = w["text"]
                break

        if not school_id:
            continue

        school_name = text.replace(school_id, "").strip()

        if not school_name:
            continue

        schools.append({
            "School ID": school_id,
            "School Name": school_name,
            "Municipality": "",
            "Division": "",
            "Region": ""
        })


def main(url_or_path):
    temp_file = None

    try:
        if url_or_path.startswith("http"):
            headers = {"User-Agent": "Mozilla/5.0"}
            resp = requests.get(url_or_path, headers=headers, timeout=60)
            resp.raise_for_status()

            fd, temp_file = tempfile.mkstemp(suffix=".pdf")
            os.close(fd)

            with open(temp_file, "wb") as f:
                f.write(resp.content)

            pdf_path = temp_file
        else:
            pdf_path = url_or_path

        if not os.path.exists(pdf_path):
            raise FileNotFoundError(pdf_path)

        schools = []

        with pdfplumber.open(pdf_path) as pdf:
            for page in pdf.pages:
                used_table = parse_tables(page, schools)
                if not used_table:
                    parse_words(page, schools)

        # No artificial truncation — return everything found
        print(json.dumps({
            "status": "success",
            "count": len(schools),
            "schools": schools
        }))

    except Exception as e:
        print(json.dumps({
            "status": "error",
            "message": str(e)
        }))

    finally:
        if temp_file and os.path.exists(temp_file):
            os.remove(temp_file)


if __name__ == "__main__":
    main(sys.argv[1])