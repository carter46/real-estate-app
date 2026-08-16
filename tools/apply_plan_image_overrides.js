/**
 * Download specific image URL sets for plans where SSR missed elevations.
 * Reads tools/plan_image_overrides.json
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const ROOT = path.resolve(__dirname, '..');
const OVERRIDES = path.join(__dirname, 'plan_image_overrides.json');
const UA =
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

async function fetchBin(url) {
  const res = await fetch(url, {
    headers: {
      'User-Agent': UA,
      Accept: 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
      Referer: 'https://www.centurycommunities.com/',
    },
  });
  if (!res.ok) throw new Error(`HTTP ${res.status} ${url}`);
  return Buffer.from(await res.arrayBuffer());
}

async function main() {
  const map = JSON.parse(fs.readFileSync(OVERRIDES, 'utf8'));
  for (const [slug, urls] of Object.entries(map)) {
    const destDir = path.join(ROOT, 'uploads', 'properties', slug);
    fs.mkdirSync(destDir, { recursive: true });
    const names = ['cover.jpg', '01.jpg', '02.jpg', '03.jpg'];
    console.log('===', slug);
    for (let i = 0; i < names.length; i++) {
      const src = urls[i] || urls[0];
      try {
        const buf = await fetchBin(src);
        if (buf.length < 5000) throw new Error(`too small (${buf.length})`);
        fs.writeFileSync(path.join(destDir, names[i]), buf);
        console.log(`  ${names[i]} ${buf.length} ${src.split('/').pop()}`);
      } catch (e) {
        console.warn(`  SKIP ${names[i]}: ${e.message}`);
      }
    }
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
  let unique = 0;
  for (const [h, slugs] of groups) {
    if (slugs.length === 1) unique++;
    else console.log('DUP', slugs.length, slugs.join(', '));
  }
  console.log(`Unique covers: ${unique}/${groups.size}`);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
