#!/usr/bin/env python3
# coding=utf-8
"""make_preview_mocks.py - render the BEFORE/AFTER chat-preview mock images for
doc/DEPLOY_REQUEST_JIM_H7901_OG.md (H7901 follow-up, 09-11-2026).

Deterministic (no timestamps/randomness). The AFTER mocks use the REAL
emitted og:title / og:description strings from the local render probes
(transcripts under Uprava docs/evidence/H7901/) and the REAL committed card
og/site_card.png - only the chat-app frame is a mock. Every image carries an
explicit MOCK label so nothing here can be mistaken for a live screenshot.

Usage:  python3 make_preview_mocks.py
Output: preview_before.png, preview_after_home.png, preview_after_mw.png
"""
import os

from PIL import Image, ImageDraw, ImageFont

HERE = os.path.dirname(os.path.abspath(__file__))
CARD = os.path.normpath(os.path.join(HERE, "..", "..", "og", "site_card.png"))

W, H = 900, 760
CHAT_BG = (221, 231, 243)      # Telegram-day chat background
BUBBLE = (255, 255, 255)
INK = (24, 34, 48)
GREY = (110, 120, 132)
LINK = (36, 110, 190)
SAFFRON_LABEL = (150, 96, 8)

SANS = (
    "/System/Library/Fonts/Supplemental/Arial.ttf",
    "/System/Library/Fonts/Helvetica.ttc",
    "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
)
SANS_BOLD = (
    "/System/Library/Fonts/Supplemental/Arial Bold.ttf",
    "/System/Library/Fonts/Supplemental/Arial Bold Italic.ttf",
    "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf",
)
SERIF_BOLD = (
    "/Users/mac/Documents/GitHub/csl-guides/static/fonts/Charis-Bold.ttf",
    "/System/Library/Fonts/Supplemental/Georgia Bold.ttf",
)


def pick(cands, size):
    for p in cands:
        if os.path.exists(p):
            return ImageFont.truetype(p, size)
    raise SystemExit("no font found among %r" % (cands,))


def wrap(draw, text, font, max_w):
    words, lines, cur = text.split(), [], ""
    for w in words:
        t = (cur + " " + w).strip()
        if draw.textlength(t, font=font) <= max_w:
            cur = t
        else:
            if cur:
                lines.append(cur)
            cur = w
    if cur:
        lines.append(cur)
    return lines


def label_strip(d, text):
    d.rectangle([0, 0, W, 54], fill=(31, 41, 51))
    size = 22
    font = pick(SANS_BOLD, size)
    while size > 12 and d.textlength(text, font=font) > W - 48:
        size -= 1
        font = pick(SANS_BOLD, size)
    d.text((24, 15), text, font=font, fill=(255, 235, 190))


def bubble(d, x, y, w, h, radius=16):
    d.rounded_rectangle([x, y, x + w, y + h], radius=radius, fill=BUBBLE,
                        outline=(206, 214, 224), width=1)


def render_before():
    img = Image.new("RGB", (W, H), CHAT_BG)
    d = ImageDraw.Draw(img)
    label_strip(d, "MOCK - BEFORE: today, the page emits no og tags at all")
    # a plain link message: no card, no title, no image
    bubble(d, 40, 110, 700, 120)
    linkfont = pick(SANS, 26)
    d.text((70, 138), "https://www.sanskrit-lexicon.uni-koeln.de/", font=linkfont, fill=LINK)
    d.text((70, 180), "Cologne Digital Sanskrit Dictionaries", font=pick(SANS, 22), fill=GREY)
    # explanatory strip
    d.rounded_rectangle([40, 300, 860, 585], radius=16, fill=(247, 249, 252),
                        outline=(206, 214, 224), width=1)
    d.text((70, 326), "What the chat app has to work with:", font=pick(SANS_BOLD, 24), fill=INK)
    for i, line in enumerate([
        "og:title        - missing",
        "og:description  - missing",
        "og:image        - missing",
        "twitter:card    - missing",
    ]):
        d.text((70, 372 + i * 38), line, font=pick(SANS, 23), fill=(178, 60, 50))
    d.text((70, 536), "-> a bare URL: nothing to show but the link itself.",
           font=pick(SANS, 22), fill=GREY)
    img.save(os.path.join(HERE, "preview_before.png"), "PNG", optimize=True)
    print("wrote preview_before.png")


def render_after(out_name, title, desc, strip):
    img = Image.new("RGB", (W, H), CHAT_BG)
    d = ImageDraw.Draw(img)
    label_strip(d, strip)
    bubble(d, 40, 78, 820, 660)
    # the real committed card, scaled to the bubble width
    card = Image.open(CARD).convert("RGB")
    cw, ch = 780, int(card.height * 780 / card.width)
    card = card.resize((cw, ch), Image.LANCZOS)
    img.paste(card, (60, 96))
    y = 96 + ch + 18
    tf = pick(SERIF_BOLD, 27)
    for line in wrap(d, title, tf, 780)[:2]:
        d.text((60, y), line, font=tf, fill=INK)
        y += 34
    y += 6
    df = pick(SANS, 23)
    for line in wrap(d, desc, df, 780)[:3]:
        d.text((60, y), line, font=df, fill=GREY)
        y += 30
    y += 8
    d.text((60, y), "sanskrit-lexicon.uni-koeln.de", font=pick(SANS, 20), fill=(150, 158, 168))
    img.save(os.path.join(HERE, out_name), "PNG", optimize=True)
    print("wrote " + out_name)


def main():
    if not os.path.exists(CARD):
        raise SystemExit("site card not found: " + CARD)
    render_before()
    render_after(
        "preview_after_home.png",
        "IITS Koeln \u2014 Cologne Digital Sanskrit Dictionaries",
        "The 44 Cologne Digital Sanskrit Dictionaries of the University of Cologne \u2014 "
        "digitized dictionaries and reference works, free online.",
        "MOCK - AFTER: homepage, og block from index_cologne.py",
    )
    render_after(
        "preview_after_mw.png",
        "Monier-Williams Sanskrit-English Dictionary \u2014 Cologne Digital Sanskrit Dictionaries",
        "Monier-Williams Sanskrit-English Dictionary (1899) \u00b7 Monier Monier-Williams \u00b7 "
        "286,558 entries. Cologne Digital Sanskrit Dictionaries \u2014 University of Cologne.",
        "MOCK - AFTER: MW landing, og block from the atlas registry row",
    )


if __name__ == "__main__":
    main()
