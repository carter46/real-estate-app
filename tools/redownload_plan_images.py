#!/usr/bin/env python3
"""Re-download unique per-plan images from Century Communities plan pages."""

from __future__ import annotations

import hashlib
import json
import re
import time
import urllib.error
import urllib.request
from collections import defaultdict
from pathlib import Path
from urllib.parse import urlparse

ROOT = Path(__file__).resolve().parents[1]
INV = Path(__file__).with_name("parent_company_inventory.json")
OUT_MAP = Path(__file__).with_name("plan_image_urls.json")
LOG = Path(__file__).with_name("plan_image_redownload.log")

UA = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"
)

ASSET_RE = re.compile(
    r"""https?://[^"'\\\s<>]+globalassets[^"'\\\s<>]+\.(?:jpe?g|png|webp)""",
    re.I,
)
REL_ASSET_RE = re.compile(
    r"""/globalassets/[^"'\\\s<>]+\.(?:jpe?g|png|webp)""",
    re.I,
)


def fetch_html(url: str) -> str:
    req = urllib.request.Request(
        url,
        headers={
            "User-Agent": UA,
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Language": "en-US,en;q=0.9",
        },
    )
    with urllib.request.urlopen(req, timeout=60) as resp:
        return resp.read().decode("utf-8", errors="replace")


def normalize(url: str) -> str:
    url = url.replace("\\u0026", "&").replace("&amp;", "&")
    return url.split("?", 1)[0]


def extract_assets(html: str) -> list[str]:
    found: set[str] = set()
    for m in ASSET_RE.finditer(html):
        found.add(normalize(m.group(0)))
    for m in REL_ASSET_RE.finditer(html):
        found.add(normalize("https://www.centurycommunities.com" + m.group(0)))
    return sorted(found)


def score_url(url: str, plan_slug: str, plan_name: str) -> int:
    u = url.lower()
    slug = plan_slug.lower()
    name = re.sub(r"^the\s+", "", plan_name, flags=re.I).lower().replace(" ", "-")
    if re.search(r"icon-|interface-|footer|eliant|svg$|logo", u):
        return -1000
    score = 0
    if re.search(r"elev|elevation|exterior|ext_|model-photos|_ext", u):
        score += 50
    if re.search(r"interior|furnished|kitchen|bedroom|great-room|primary", u):
        score += 20
    if slug and slug in u:
        score += 80
    if name and name in u:
        score += 60
    if re.search(r"floor-\d|floorplan|floor_plan", u):
        score += 5
    if "aerial" in u:
        score += 10
    return score


def pick_images(urls: list[str], plan_slug: str, plan_name: str, need: int = 4) -> list[str]:
    ranked = []
    for u in urls:
        s = score_url(u, plan_slug, plan_name)
        if s > 0:
            ranked.append((s, u))
    ranked.sort(key=lambda x: x[0], reverse=True)
    picked: list[str] = []
    seen: set[str] = set()
    for _, u in ranked:
        base = Path(urlparse(u).path).stem.lower()
        if base in seen:
            continue
        seen.add(base)
        picked.append(u)
        if len(picked) >= need:
            break
    return picked


def download(url: str, dest: Path) -> int:
    dest.parent.mkdir(parents=True, exist_ok=True)
    tmp = dest.with_suffix(dest.suffix + ".part")
    req = urllib.request.Request(url, headers={"User-Agent": UA})
    with urllib.request.urlopen(req, timeout=90) as resp, open(tmp, "wb") as f:
        data = resp.read()
        f.write(data)
    tmp.replace(dest)
    return len(data)


def main() -> None:
    inv = json.loads(INV.read_text(encoding="utf-8"))
    results = []
    lines: list[str] = [f"Started"]

    for p in inv["properties"]:
        slug = p["slug"]
        plan_slug = p["plan_slug"]
        plan = p["plan"]
        url = p["url"]
        print(f"=== {slug} ({plan}) ===")
        try:
            html = fetch_html(url)
        except Exception as e:
            msg = f"FAIL fetch {slug}: {e}"
            print(msg)
            lines.append(msg)
            results.append({"slug": slug, "ok": False, "error": str(e)})
            continue

        assets = extract_assets(html)
        print(f"  assets found: {len(assets)}")
        picked = pick_images(assets, plan_slug, plan, 4)
        if not picked:
            msg = f"FAIL: no plan images for {slug}"
            print(msg)
            lines.append(msg)
            results.append({"slug": slug, "ok": False, "error": "no images", "assets": len(assets)})
            continue

        dest_dir = ROOT / "uploads" / "properties" / slug
        names = ["cover.jpg", "01.jpg", "02.jpg", "03.jpg"]
        saved = []
        for i, name in enumerate(names):
            src = picked[i] if i < len(picked) else picked[0]
            dest = dest_dir / name
            try:
                nbytes = download(src, dest)
                print(f"  {name} <- {src} ({nbytes} bytes)")
                saved.append({"file": name, "url": src, "bytes": nbytes})
                lines.append(f"{slug} {name} {src}")
            except Exception as e:
                msg = f"FAIL download {slug} {name}: {e}"
                print(msg)
                lines.append(msg)
        results.append(
            {
                "slug": slug,
                "plan": plan,
                "url": url,
                "ok": bool(saved),
                "image_urls": picked,
                "saved": saved,
            }
        )
        time.sleep(0.35)

    # uniqueness
    groups: dict[str, list[str]] = defaultdict(list)
    for cover in sorted((ROOT / "uploads" / "properties").glob("cc-*/cover.jpg")):
        h = hashlib.md5(cover.read_bytes()).hexdigest()
        groups[h].append(cover.parent.name)

    dupes = {h: slugs for h, slugs in groups.items() if len(slugs) > 1}
    unique = sum(1 for slugs in groups.values() if len(slugs) == 1)
    print("--- cover hash groups ---")
    for h, slugs in sorted(groups.items(), key=lambda kv: (-len(kv[1]), kv[0])):
        line = f"{len(slugs)}x {h} -> {', '.join(slugs)}"
        print(line)
        lines.append(line)

    payload = {
        "unique_cover_hashes": unique,
        "duplicate_cover_groups": [
            {"hash": h, "count": len(s), "slugs": s} for h, s in dupes.items()
        ],
        "properties": results,
    }
    OUT_MAP.write_text(json.dumps(payload, indent=2), encoding="utf-8")
    LOG.write_text("\n".join(lines) + "\n", encoding="utf-8")
    print(f"Wrote {OUT_MAP}")
    print(f"Unique covers: {unique} / {len(results)}")


if __name__ == "__main__":
    main()
