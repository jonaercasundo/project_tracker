# PDF Extraction Service

A small FastAPI microservice that Laravel (or anything else) calls over HTTP
to pull structured content out of inconsistent, unpredictable PDFs — including
scanned/image-only pages.

## How it decides text vs OCR

For each page:
1. Try `pdfplumber` for native text + tables (fast, accurate for real PDFs).
2. If that page has fewer than 20 characters of text **and** no tables, it's
   treated as scanned/image-only, and OCR (`pytesseract` on a rasterized
   version of the page via `pdf2image`) is used instead.

Every page in the response says which method (`"text"` or `"ocr"`) produced
it, plus an OCR confidence score when relevant — so the downstream review
step knows how much to trust each page.

**Known limitation:** OCR currently returns plain text only, not structured
tables. If a scanned page contains a table, you'll get its content as raw
text and will need to map it manually in the review step, or extend this
service later with layout-aware OCR / a vision model.

## Setup

System dependencies (already used via `tesseract` and `pdftoppm`):
```bash
sudo apt-get install -y tesseract-ocr poppler-utils
```

Python dependencies:
```bash
pip install -r requirements.txt
```

## Running

```bash
python3 -m uvicorn main:app --host 0.0.0.0 --port 8000
```

## Endpoints

### `GET /health`
Liveness check. Returns `{"status": "ok"}`.

### `POST /extract`
Upload a PDF directly.
```bash
curl -X POST http://localhost:8000/extract -F "file=@allocation_report.pdf"
```

### `POST /extract-url`
Extract from a PDF at a URL (e.g. a government site link).
```bash
curl -X POST http://localhost:8000/extract-url \
  -H "Content-Type: application/json" \
  -d '{"url": "https://example.gov/allocation_report.pdf"}'
```

### Response shape
```json
{
  "status": "success",
  "source": "allocation_report.pdf",
  "report_title": "Barangay Allocation Report - Region IV-A",
  "pages": 3,
  "extraction_summary": {
    "text_pages": 2,
    "ocr_pages": 1,
    "empty_pages": 0,
    "tables_found": 4
  },
  "pages_detail": [
    {
      "page_number": 1,
      "method": "text",
      "char_count": 842,
      "ocr_confidence": null,
      "text": "...",
      "tables": [
        {
          "headers": ["Barangay", "Project", "Amount", "Status"],
          "rows": [
            {"Barangay": "San Isidro", "Project": "Road repair", "Amount": "150,000", "Status": "Ongoing"}
          ]
        }
      ]
    }
  ]
}
```

## Calling this from Laravel

```php
$response = Http::attach(
    'file', file_get_contents($pdfPath), basename($pdfPath)
)->post('http://extraction-service:8000/extract');

$data = $response->json();
// Store $data['pages_detail'] as raw_documents.raw_json in your staging table
```

## Next steps in the pipeline

This service only extracts — it doesn't classify format, guess field
mappings, or write to a database. Those are the next stages:

1. **Staging DB** — store this raw JSON + a status flag per document.
2. **Classification** — guess which known template a document matches
   (or flag it unknown) and pre-fill a column mapping.
3. **Human review UI** (Laravel) — let someone confirm/edit the mapping
   and fix OCR mistakes before anything is imported.
4. **Final import** — write confirmed rows into your real app tables.