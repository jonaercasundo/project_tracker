"""
PDF extraction microservice.

Endpoints:
  POST /extract        - upload a PDF file directly (multipart/form-data)
  POST /extract-url     - extract from a PDF reachable at a URL
  GET  /health          - liveness check

Laravel (or anything else) calls this service over HTTP and gets back
structured JSON: per-page text, tables, and which extraction method
(native text vs OCR) was used for each page. Nothing here writes to a
database -- that's the job of the staging layer downstream.
"""

import os
import tempfile

import requests
from fastapi import FastAPI, File, HTTPException, UploadFile
from fastapi.responses import JSONResponse
from pydantic import BaseModel

from extractor import extract_pdf

app = FastAPI(title="PDF Extraction Service", version="0.1.0")

MAX_FILE_SIZE_MB = 50


class UrlRequest(BaseModel):
    url: str


@app.get("/health")
def health():
    return {"status": "ok"}


@app.post("/extract")
async def extract(file: UploadFile = File(...)):
    if not file.filename.lower().endswith(".pdf"):
        raise HTTPException(status_code=400, detail="Only .pdf files are accepted")

    fd, temp_path = tempfile.mkstemp(suffix=".pdf")
    os.close(fd)

    try:
        contents = await file.read()

        if len(contents) > MAX_FILE_SIZE_MB * 1024 * 1024:
            raise HTTPException(
                status_code=413,
                detail=f"File exceeds {MAX_FILE_SIZE_MB}MB limit",
            )

        with open(temp_path, "wb") as f:
            f.write(contents)

        result = extract_pdf(temp_path, source_name=file.filename)
        return JSONResponse(content=result)

    except HTTPException:
        raise
    except Exception as e:
        return JSONResponse(
            status_code=422,
            content={"status": "error", "message": str(e)},
        )
    finally:
        if os.path.exists(temp_path):
            os.remove(temp_path)


@app.post("/extract-url")
def extract_from_url(payload: UrlRequest):
    if not payload.url.startswith(("http://", "https://")):
        raise HTTPException(status_code=400, detail="url must start with http:// or https://")

    fd, temp_path = tempfile.mkstemp(suffix=".pdf")
    os.close(fd)

    try:
        response = requests.get(
            payload.url,
            headers={"User-Agent": "Mozilla/5.0"},
            timeout=60,
        )
        response.raise_for_status()

        with open(temp_path, "wb") as f:
            f.write(response.content)

        result = extract_pdf(temp_path, source_name=payload.url)
        return JSONResponse(content=result)

    except requests.RequestException as e:
        return JSONResponse(
            status_code=502,
            content={"status": "error", "message": f"Could not download PDF: {e}"},
        )
    except Exception as e:
        return JSONResponse(
            status_code=422,
            content={"status": "error", "message": str(e)},
        )
    finally:
        if os.path.exists(temp_path):
            os.remove(temp_path)


if __name__ == "__main__":
    import uvicorn

    uvicorn.run(app, host="0.0.0.0", port=8000)