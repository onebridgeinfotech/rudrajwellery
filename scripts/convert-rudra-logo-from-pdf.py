#!/usr/bin/env python3
"""Convert Rudra jewellery.pdf to web-ready PNG logos."""

from __future__ import annotations

import sys
from pathlib import Path

try:
    import fitz  # PyMuPDF
except ImportError:
    print("Install PyMuPDF: pip install pymupdf")
    sys.exit(1)

ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "wordpress-theme" / "jwellery-jewelry" / "assets" / "images"
PDF_CANDIDATES = [
    ROOT / "Rudra jewellery.pdf",
    ROOT / "rudra jewellery.pdf",
    Path.home() / "Downloads" / "Rudra jewellery.pdf",
]


def find_pdf() -> Path:
    for candidate in PDF_CANDIDATES:
        if candidate.exists():
            return candidate
    raise FileNotFoundError("Rudra jewellery.pdf not found")


def render_page(pdf_path: Path, zoom: float = 4.0) -> fitz.Pixmap:
    doc = fitz.open(pdf_path)
    page = doc[0]
    matrix = fitz.Matrix(zoom, zoom)
    pix = page.get_pixmap(matrix=matrix, alpha=True)
    doc.close()
    return pix


def save_pixmap(pix: fitz.Pixmap, path: Path) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    pix.save(str(path))


def pixmap_to_image(pix: fitz.Pixmap) -> "Image.Image":
    from io import BytesIO

    from PIL import Image

    return Image.open(BytesIO(pix.tobytes("png"))).convert("RGBA")


def remove_dark_background(image: "Image.Image", threshold: int = 52) -> "Image.Image":
    """Make near-black PDF backdrop transparent for white headers."""
    pixels = image.load()
    for y in range(image.height):
        for x in range(image.width):
            r, g, b, a = pixels[x, y]
            if a == 0:
                continue
            if r <= threshold and g <= threshold and b <= threshold:
                pixels[x, y] = (r, g, b, 0)
    return image


def trim_image(image: "Image.Image") -> "Image.Image":
    bbox = image.getbbox()
    return image.crop(bbox) if bbox else image


def main() -> None:
    pdf_path = find_pdf()
    print(f"Source: {pdf_path}")

    pix = render_page(pdf_path, zoom=5.0)

    try:
        from PIL import Image

        im = remove_dark_background(trim_image(pixmap_to_image(pix)))
        master = OUT_DIR / "rudra-logo.png"
        master.parent.mkdir(parents=True, exist_ok=True)
        im.save(master, optimize=True)
        print(f"Saved {master} ({im.size[0]}x{im.size[1]})")

        def resize_to_height(image: Image.Image, target_h: int) -> Image.Image:
            ratio = target_h / image.height
            target_w = max(1, int(image.width * ratio))
            return image.resize((target_w, target_h), Image.Resampling.LANCZOS)

        header = resize_to_height(im, 128)
        footer = resize_to_height(im, 80)

        header_path = OUT_DIR / "rudra-logo-header.png"
        footer_path = OUT_DIR / "rudra-logo-footer.png"
        header.save(header_path, optimize=True)
        footer.save(footer_path, optimize=True)
        print(f"Saved {header_path} ({header.size[0]}x{header.size[1]})")
        print(f"Saved {footer_path} ({footer.size[0]}x{footer.size[1]})")
    except Exception as exc:  # noqa: BLE001
        master = OUT_DIR / "rudra-logo.png"
        save_pixmap(pix, master)
        print(f"Variant export failed ({exc}); saved raw master only.")


if __name__ == "__main__":
    main()
