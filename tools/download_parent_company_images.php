<?php
/**
 * Download Century Communities images for parent-company import and emit SQL fragments.
 * Usage: php tools/download_parent_company_images.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$jsonPath = __DIR__ . '/parent_company_inventory.json';
$data = json_decode((string) file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);

function download_url(string $url, string $dest): bool
{
    $clean = preg_replace('/\?.*$/', '', $url) ?: $url;
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 60,
            'header' => "User-Agent: SDC-Import/1.0\r\nAccept: image/*\r\n",
            'follow_location' => 1,
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $bin = @file_get_contents($clean, false, $ctx);
    if ($bin === false || strlen($bin) < 1000) {
        // try with original query string
        $bin = @file_get_contents($url, false, $ctx);
    }
    if ($bin === false || strlen($bin) < 1000) {
        return false;
    }
    $dir = dirname($dest);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($dest, $bin) !== false;
}

function ext_from_url(string $url): string
{
    $path = parse_url(preg_replace('/\?.*$/', '', $url) ?: $url, PHP_URL_PATH) ?: '';
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return $ext === 'jpeg' ? 'jpg' : $ext;
    }
    return 'jpg';
}

$manifest = [];
$ok = 0;
$fail = 0;

foreach ($data['regions'] as $region) {
    $ext = ext_from_url($region['cover_url']);
    $rel = 'uploads/regions/' . $region['slug'] . '.' . $ext;
    $dest = $root . '/' . $rel;
    if (download_url($region['cover_url'], $dest)) {
        echo "OK region {$region['slug']}\n";
        $ok++;
        $region['image_path'] = $rel;
    } else {
        echo "FAIL region {$region['slug']}\n";
        $fail++;
        $region['image_path'] = null;
    }
}

// rewrite regions with image_path for SQL generation later
$regionsOut = [];
foreach ($data['regions'] as $i => $region) {
    $ext = ext_from_url($region['cover_url']);
    $rel = 'uploads/regions/' . $region['slug'] . '.' . $ext;
    $regionsOut[] = array_merge($region, [
        'image_path' => is_file($root . '/' . $rel) ? $rel : null,
    ]);
}

$galleries = $data['community_galleries'];
$propsOut = [];

foreach ($data['properties'] as $p) {
    $stateKey = strtolower($p['state']);
    $urls = $galleries[$stateKey] ?? [];
    $dirRel = 'uploads/properties/' . $p['slug'];
    $dirAbs = $root . '/' . $dirRel;
    if (!is_dir($dirAbs)) {
        mkdir($dirAbs, 0755, true);
    }
    $files = [];
    foreach ($urls as $idx => $url) {
        $ext = ext_from_url($url);
        $name = $idx === 0 ? ('cover.' . $ext) : (sprintf('%02d.%s', $idx, $ext));
        $dest = $dirAbs . '/' . $name;
        if (download_url($url, $dest)) {
            $files[] = [
                'path' => $dirRel . '/' . $name,
                'is_cover' => $idx === 0 ? 1 : 0,
                'sort_order' => $idx === 0 ? 0 : ($idx * 10),
            ];
            $ok++;
            echo "OK {$p['slug']}/{$name}\n";
        } else {
            $fail++;
            echo "FAIL {$p['slug']} img{$idx}\n";
        }
    }
    // Ensure at least cover if first failed but later succeeded
    if ($files !== [] && $files[0]['is_cover'] !== 1) {
        $files[0]['is_cover'] = 1;
        $files[0]['sort_order'] = 0;
    }
    $propsOut[] = array_merge($p, ['images' => $files]);
    $manifest[] = [
        'slug' => $p['slug'],
        'ref' => $p['ref'],
        'source_url' => $p['url'],
        'agent' => $p['agent'],
        'state' => $p['state'],
        'images' => array_column($files, 'path'),
    ];
}

$out = [
    'regions' => $regionsOut,
    'properties' => $propsOut,
    'downloaded_ok' => $ok,
    'downloaded_fail' => $fail,
];
file_put_contents(__DIR__ . '/parent_company_inventory.downloaded.json', json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
file_put_contents($root . '/database/parent_company_import_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "\nDone ok={$ok} fail={$fail}\n";
