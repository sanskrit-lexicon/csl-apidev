#!/usr/bin/env python3
# coding=utf-8
"""make_site_card.py - render og/site_card.png (1200x630), the ONE
Cologne-owned Open Graph card shared by every web surface (H7901).

Design: light-minimal-academic (ivory ground, dark serif title, thin saffron
rule, small-caps brand line) - the same visual language MG grilled for the
csl-guides per-page cards (H7886). Typeface: Charis SIL (SIL OFL, already
vendored in csl-guides; the PNG raster embeds no font data). Deterministic:
no timestamps, no randomness - re-running overwrites with identical pixels.

Usage:
    python3 make_site_card.py [--out og/site_card.png]

The card is referenced by absolute URL from og_meta.php ($OG_SITE['image']);
nothing points at kosha or samskrtam.ru (H6478 ruling).
"""
import os
import sys

from PIL import Image, ImageDraw, ImageFont

W, H = 1200, 630
IVORY = (250, 247, 239)
INK = (31, 41, 51)
SAFFRON = (199, 125, 10)
GREY = (94, 104, 116)

FONT_CANDIDATES = (
    "/Users/mac/Documents/GitHub/csl-guides/static/fonts/Charis-Bold.ttf",
    "/System/Library/Fonts/Supplemental/Georgia.ttf",
    "/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf",
)
FONT_CANDIDATES_REG = (
    "/Users/mac/Documents/GitHub/csl-guides/static/fonts/Charis-Regular.ttf",
    "/System/Library/Fonts/Supplemental/Georgia.ttf",
    "/usr/share/fonts/truetype/dejavu/DejaVuSerif.ttf",
)


def pick(candidates, size):
    for path in candidates:
        if os.path.exists(path):
            return ImageFont.truetype(path, size)
    raise SystemExit("no candidate font found; looked at: %r" % (candidates,))


def spaced(draw, xy, text, font, fill, tracking=0):
    """Draw text with extra letter spacing (PIL has no tracking API)."""
    x, y = xy
    for ch in text:
        draw.text((x, y), ch, font=font, fill=fill)
        x += draw.textlength(ch, font=font) + tracking
    return x - xy[0]


def main():
    out = sys.argv[sys.argv.index("--out") + 1] if "--out" in sys.argv else (
        os.path.join(os.path.dirname(os.path.abspath(__file__)), "og", "site_card.png")
    )
    title_font = pick(FONT_CANDIDATES, 84)
    brand_font = pick(FONT_CANDIDATES_REG, 30)
    sub_font = pick(FONT_CANDIDATES_REG, 34)
    foot_font = pick(FONT_CANDIDATES_REG, 27)

    img = Image.new("RGB", (W, H), IVORY)
    d = ImageDraw.Draw(img)

    # small-caps brand line + thin saffron rule
    spaced(d, (90, 78), "UNIVERSITÄT ZU KÖLN", brand_font, GREY, tracking=6)
    d.rectangle([90, 138, 250, 144], fill=SAFFRON)

    # title, wrapped onto two lines
    d.text((88, 190), "Cologne Digital", font=title_font, fill=INK)
    d.text((88, 292), "Sanskrit Dictionaries", font=title_font, fill=INK)

    # one-line standfirst (image copy, not an og:description - those are
    # generated from data in og_meta.php)
    d.text((92, 432), "Digitized dictionaries and reference works,", font=sub_font, fill=GREY)
    d.text((92, 478), "free online from the University of Cologne.", font=sub_font, fill=GREY)

    # footer URL
    d.text((92, 566), "sanskrit-lexicon.uni-koeln.de", font=foot_font, fill=SAFFRON)

    os.makedirs(os.path.dirname(out), exist_ok=True)
    img.save(out, "PNG", optimize=True)
    print("wrote %s (%dx%d)" % (out, W, H))


if __name__ == "__main__":
    main()
