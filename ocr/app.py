from fastapi import FastAPI, File, UploadFile, HTTPException, Form
from pdf2image import convert_from_bytes
import pytesseract

app = FastAPI(title="Analytica OCR")


@app.get("/health")
def health():
    return {"ok": True}


@app.post("/ocr")
async def ocr(
    file: UploadFile = File(...),
    lang: str = Form("fra"),
    first_page: int = Form(1),
    max_pages: int = Form(5),
    dpi: int = Form(250),
    psm: int = Form(6),
    preprocess: bool = Form(True),
):
    if file.content_type not in ("application/pdf", "application/octet-stream"):
        # Some browsers upload pdf as octet-stream
        raise HTTPException(status_code=415, detail=f"Unsupported content-type: {file.content_type}")

    pdf_bytes = await file.read()
    if not pdf_bytes or pdf_bytes[:4] != b"%PDF":
        raise HTTPException(status_code=400, detail="Invalid PDF")

    if first_page < 1:
        first_page = 1
    if max_pages < 1:
        max_pages = 1
    if max_pages > 50:
        max_pages = 50
    if psm < 3 or psm > 12:
        psm = 6

    last_page = first_page + max_pages - 1

    # Convert PDF pages to images and OCR.
    try:
        images = convert_from_bytes(
            pdf_bytes,
            dpi=dpi,
            fmt="png",
            first_page=first_page,
            last_page=last_page,
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"pdf2image failed: {e}")

    texts = []
    for img in images:
        try:
            variants = [img]
            if preprocess:
                gray = img.convert("L")
                boosted = gray.point(lambda px: 255 if px > 165 else 0)
                variants = [boosted, gray, img]

            best = ""
            seen_lines = set()
            merged_lines = []

            for variant in variants:
                txt = pytesseract.image_to_string(
                    variant,
                    lang=lang,
                    config=f"--oem 1 --psm {psm} -c preserve_interword_spaces=1",
                )

                if len(txt) > len(best):
                    best = txt

                for line in txt.splitlines():
                    normalized = line.strip()
                    if not normalized:
                        continue
                    if normalized in seen_lines:
                        continue
                    seen_lines.add(normalized)
                    merged_lines.append(normalized)

            txt = "\n".join(merged_lines) if merged_lines else best
        except Exception as e:
            raise HTTPException(status_code=500, detail=f"tesseract failed: {e}")
        if txt:
            texts.append(txt)

    return {"text": "\n".join(texts)}
