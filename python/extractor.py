"""
Core PDF extraction logic.

Strategy per page:
1. Try native text/table extraction with pdfplumber (fast, accurate when the
   PDF has a real text layer).
2. If a page has no usable text AND no tables, treat it as a scanned/image
   page and fall back to OCR (pytesseract) on a rasterized version of that
   page (pdf2image + poppler).

Every page reports which method produced its content, so downstream stages
(classification, human review) know how much to trust it.
"""

import re
from dataclasses import dataclass, field
from typing import Optional

import pdfplumber
import pytesseract
from pdf2image import convert_from_path
import os

POPPLER_PATH = r"E:\poppler-26.02.0\Library\bin"   # <-- change if different
# Below this many extracted characters, a page is considered "no usable text"
# even if pdfplumber returned something (e.g. stray artifacts, page numbers).
pytesseract.pytesseract.tesseract_cmd = (
    r"C:\Program Files\Tesseract-OCR\tesseract.exe"
)
MIN_TEXT_CHARS = 20

# DPI for rasterizing pages before OCR. Higher = better accuracy, slower.
OCR_DPI = 300


def clean_cell(value) -> str:
    if value is None:
        return ""
    return re.sub(r"\s+", " ", str(value)).strip()


@dataclass
class TableResult:
    headers: list
    rows: list


@dataclass
class PageResult:
    page_number: int
    method: str  # "text" | "ocr" | "empty"
    text: str
    tables: list = field(default_factory=list)
    char_count: int = 0
    ocr_confidence: Optional[float] = None


def _extract_tables_from_page(page) -> list:
    """Pull tables from a pdfplumber page, deduplicating header collisions."""
    results = []

    for table in page.extract_tables():
        if len(table) < 2:
            continue

        raw_headers = [clean_cell(c) for c in table[0]]

        # De-duplicate headers so repeated/blank column names don't
        # silently overwrite each other's values.
        seen = {}
        headers = []
        for i, h in enumerate(raw_headers):
            key = h if h else f"Column {i + 1}"
            if key in seen:
                seen[key] += 1
                key = f"{key} ({seen[key]})"
            else:
                seen[key] = 1
            headers.append(key)

        rows = []
        for raw_row in table[1:]:
            values = [clean_cell(c) for c in raw_row]
            while len(values) < len(headers):
                values.append("")
            rows.append({headers[i]: values[i] for i in range(len(headers))})

        results.append({"headers": headers, "rows": rows})

    return results


def _ocr_page(pdf_path: str, page_number: int) -> tuple[str, Optional[float]]:
    """Rasterize a single page and run OCR on it. Returns (text, mean_confidence)."""
    images = convert_from_path(
        pdf_path,
        dpi=OCR_DPI,
        first_page=page_number,
        last_page=page_number,
        poppler_path=POPPLER_PATH if os.path.exists(POPPLER_PATH) else None,
    )

    if not images:
        return "", None

    image = images[0]

    text = pytesseract.image_to_string(image)

    # Pull per-word confidences to give the caller a rough quality signal.
    data = pytesseract.image_to_data(image, output_type=pytesseract.Output.DICT)
    confidences = [int(c) for c in data.get("conf", []) if c not in ("-1", -1)]
    mean_conf = round(sum(confidences) / len(confidences), 1) if confidences else None

    return text.strip(), mean_conf


def extract_report_title(pdf) -> str:
    """First non-empty line of the first page's text, if available."""
    if len(pdf.pages) == 0:
        return ""

    text = pdf.pages[0].extract_text()
    if not text:
        return ""

    for line in text.splitlines():
        line = line.strip()
        if line:
            return line

    return ""


def extract_pdf(pdf_path: str, source_name: str) -> dict:
    pages_detail = []
    text_pages = 0
    ocr_pages = 0
    total_tables = 0

    with pdfplumber.open(pdf_path) as pdf:
        report_title = extract_report_title(pdf)
        total_pages = len(pdf.pages)

        for i, page in enumerate(pdf.pages, start=1):
            native_text = (page.extract_text() or "").strip()
            tables = _extract_tables_from_page(page)

            has_usable_text = len(native_text) >= MIN_TEXT_CHARS
            has_tables = len(tables) > 0

            if has_usable_text or has_tables:
                pages_detail.append(
                    PageResult(
                        page_number=i,
                        method="text",
                        text=native_text,
                        tables=tables,
                        char_count=len(native_text),
                    )
                )
                text_pages += 1
                total_tables += len(tables)
            else:
                # Likely a scanned/image page: fall back to OCR.
                ocr_text, ocr_conf = _ocr_page(pdf_path, i)
                pages_detail.append(
                    PageResult(
                        page_number=i,
                        method="ocr" if ocr_text else "empty",
                        text=ocr_text,
                        tables=[],  # structured tables from OCR are out of
                        # scope for this pass -- see README for the
                        # planned follow-up (layout-aware OCR / vision model).
                        char_count=len(ocr_text),
                        ocr_confidence=ocr_conf,
                    )
                )
                if ocr_text:
                    ocr_pages += 1

    return {
        "status": "success",
        "source": source_name,
        "report_title": report_title,
        "pages": total_pages,
        "extraction_summary": {
            "text_pages": text_pages,
            "ocr_pages": ocr_pages,
            "empty_pages": total_pages - text_pages - ocr_pages,
            "tables_found": total_tables,
        },
        "pages_detail": [
            {
                "page_number": p.page_number,
                "method": p.method,
                "char_count": p.char_count,
                "ocr_confidence": p.ocr_confidence,
                "text": p.text,
                "tables": p.tables,
            }
            for p in pages_detail
        ],
    }