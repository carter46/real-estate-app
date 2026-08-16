/**
 * Re-download unique per-plan images from Century Communities plan pages.
 * Overwrites cover.jpg / 01.jpg / 02.jpg / 03.jpg under uploads/properties/{slug}/
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { URL } = require('url');

const ROOT = path.resolve(__dirname, '..');
const INV = path.join(__dirname, 'parent_company_inventory.json');
const OUT_MAP = path.join(__dirname, 'plan_image_urls.json');
const LOG = path.join(__dirname, 'plan_image_redownload.log');

const UA =
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

const ASSET_RE =
  /https?:\/\/[^"'\\\s<>]+globalassets[^"'\\\s<>]+\.(?:jpe?g|png|webp)/gi;
const REL_ASSET_RE = /\/globalassets\/[^"'\\\s<>]+\.(?:jpe?g|png|webp)/gi;

function normalize(u) {
  return u.replace(/\\u0026/g, '&').replace(/&amp;/g, '&').split('?')[0];
}

async function fetchText(url) {
  const res = await fetch(url, {
    headers: {
      'User-Agent': UA,
      Accept: 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
      'Accept-Language': 'en-US,en;q=0.9',
    },
  });
  if (!res.ok) throw new Error(`HTTP ${res.status} for ${url}`);
  return await res.text();
}

async function fetchBin(url) {
  const res = await fetch(url, { headers: { 'User-Agent': UA } });
  if (!res.ok) throw new Error(`HTTP ${res.status} for ${url}`);
  const buf = Buffer.from(await res.arrayBuffer());
  return buf;
}

function extractAssets(html) {
  const found = new Set();
  for (const m of html.matchAll(ASSET_RE)) found.add(normalize(m[0]));
  for (const m of html.matchAll(REL_ASSET_RE)) {
    found.add(normalize('https://www.centurycommunities.com' + m[0]));
  }
  return [...found];
}

function scoreUrl(url, planSlug, planName, forCover = false) {
  const u = url.toLowerCase();
  const slug = String(planSlug || '').toLowerCase();
  const name = String(planName || '')
    .replace(/^the\s+/i, '')
    .toLowerCase()
    .replace(/\s+/g, '-');
  if (/icon-|interface-|footer|eliant|svg$|logo/.test(u)) return -1000;
  let score = 0;
  const isElev = /elev|elevation|exterior|extlike|_ext|ext_/.test(u);
  const isInterior = /interior|furnished|kitchen|bedroom|great-room|primary|dining/.test(u);
  const isFloor = /floor-\d|floor_1|floor_2|floorplan|floor-plan/.test(u) && !isElev;
  if (isElev) score += forCover ? 120 : 70;
  if (isInterior) score += forCover ? 40 : 90;
  if (isFloor) score += forCover ? -40 : 15;
  if (slug && u.includes(slug)) score += 80;
  if (name && u.includes(name)) score += 60;
  if (u.includes('aerial')) score += forCover ? 30 : 10;
  if (u.includes('model-photos')) score += forCover ? 50 : 30;
  return score;
}

function pickImages(urls, planSlug, planName, need = 4) {
  const rank = (forCover) =>
    urls
      .map((u) => ({ u, s: scoreUrl(u, planSlug, planName, forCover) }))
      .filter((x) => x.s > 0)
      .sort((a, b) => b.s - a.s);

  const coverRanked = rank(true);
  const galleryRanked = rank(false);
  const picked = [];
  const seen = new Set();

  const take = (list) => {
    for (const { u } of list) {
      const base = path
        .basename(new URL(u).pathname)
        .replace(/\.[^.]+$/, '')
        .toLowerCase();
      if (seen.has(base)) continue;
      seen.add(base);
      picked.push(u);
      if (picked.length >= need) return true;
    }
    return false;
  };

  // Cover first from elevation-biased ranking, then fill gallery.
  take(coverRanked.slice(0, 1));
  take(galleryRanked);
  if (picked.length < need) take(coverRanked);
  return picked;
}

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

async function main() {
  const inv = JSON.parse(fs.readFileSync(INV, 'utf8'));
  const results = [];
  const lines = [`Started ${new Date().toISOString()}`];

  for (const p of inv.properties) {
    const { slug, plan_slug: planSlug, plan, url } = p;
    console.log(`=== ${slug} (${plan}) ===`);
    let html;
    try {
      html = await fetchText(url);
    } catch (e) {
      const msg = `FAIL fetch ${slug}: ${e.message}`;
      console.warn(msg);
      lines.push(msg);
      results.push({ slug, ok: false, error: e.message });
      continue;
    }
    const assets = extractAssets(html);
    console.log(`  assets found: ${assets.length}`);
    const picked = pickImages(assets, planSlug, plan, 4);
    if (!picked.length) {
      const msg = `FAIL: no plan images for ${slug}`;
      console.warn(msg);
      lines.push(msg);
      results.push({ slug, ok: false, error: 'no images', assets: assets.length });
      continue;
    }

    const destDir = path.join(ROOT, 'uploads', 'properties', slug);
    fs.mkdirSync(destDir, { recursive: true });
    const names = ['cover.jpg', '01.jpg', '02.jpg', '03.jpg'];
    const saved = [];
    for (let i = 0; i < names.length; i++) {
      const src = picked[i] || picked[0];
      const dest = path.join(destDir, names[i]);
      try {
        const buf = await fetchBin(src);
        fs.writeFileSync(dest, buf);
        console.log(`  ${names[i]} <- ${src} (${buf.length} bytes)`);
        saved.push({ file: names[i], url: src, bytes: buf.length });
        lines.push(`${slug} ${names[i]} ${src}`);
      } catch (e) {
        const msg = `FAIL download ${slug} ${names[i]}: ${e.message}`;
        console.warn(msg);
        lines.push(msg);
      }
    }
    results.push({ slug, plan, url, ok: saved.length > 0, image_urls: picked, saved });
    await sleep(350);
  }

  const groups = new Map();
  for (const dir of fs.readdirSync(path.join(ROOT, 'uploads', 'properties'))) {
    if (!dir.startsWith('cc-')) continue;
    const cover = path.join(ROOT, 'uploads', 'properties', dir, 'cover.jpg');
    if (!fs.existsSync(cover)) continue;
    const h = crypto.createHash('md5').update(fs.readFileSync(cover)).digest('hex');
    if (!groups.has(h)) groups.set(h, []);
    groups.get(h).push(dir);
  }

  console.log('--- cover hash groups ---');
  const dupes = [];
  let unique = 0;
  for (const [h, slugs] of [...groups.entries()].sort((a, b) => b[1].length - a[1].length)) {
    const line = `${slugs.length}x ${h} -> ${slugs.join(', ')}`;
    console.log(line);
    lines.push(line);
    if (slugs.length === 1) unique += 1;
    else dupes.push({ hash: h, count: slugs.length, slugs });
  }

  const payload = {
    generated: new Date().toISOString(),
    unique_cover_hashes: unique,
    duplicate_cover_groups: dupes,
    properties: results,
  };
  fs.writeFileSync(OUT_MAP, JSON.stringify(payload, null, 2));
  fs.writeFileSync(LOG, lines.join('\n') + '\n');
  console.log(`Wrote ${OUT_MAP}`);
  console.log(`Unique covers: ${unique} / ${results.length}`);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
