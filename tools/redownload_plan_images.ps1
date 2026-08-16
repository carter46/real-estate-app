# Re-download unique per-plan images from Century Communities plan pages.
# Overwrites cover.jpg / 01.jpg / 02.jpg / 03.jpg under uploads/properties/{slug}/

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot
$InvPath = Join-Path $PSScriptRoot 'parent_company_inventory.json'
$OutMap = Join-Path $PSScriptRoot 'plan_image_urls.json'
$LogPath = Join-Path $PSScriptRoot 'plan_image_redownload.log'

$inv = Get-Content -Raw -Path $InvPath | ConvertFrom-Json
$ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'
$headers = @{
    'User-Agent' = $ua
    'Accept' = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
    'Accept-Language' = 'en-US,en;q=0.9'
}

function Normalize-Url([string]$Raw) {
    $u = $Raw -replace '\\u0026', '&'
    $u = $u.Replace('&amp;', '&')
    return $u.Split('?')[0]
}

function Get-Html([string]$Url) {
    try {
        $resp = Invoke-WebRequest -Uri $Url -Headers $headers -UseBasicParsing -TimeoutSec 60
        return [string]$resp.Content
    } catch {
        Write-Warning ("Fetch failed: {0} — {1}" -f $Url, $_.Exception.Message)
        return ''
    }
}

function Extract-AssetUrls([string]$Html) {
    $set = New-Object 'System.Collections.Generic.HashSet[string]' ([StringComparer]::OrdinalIgnoreCase)
    if ([string]::IsNullOrWhiteSpace($Html)) { return @() }
    $rx = [regex]'https?://[^"''\s<>]+globalassets[^"''\s<>]+\.(?:jpe?g|png|webp)'
    foreach ($m in $rx.Matches($Html)) {
        [void]$set.Add((Normalize-Url $m.Value))
    }
    $rx2 = [regex]'/globalassets/[^"''\s<>]+\.(?:jpe?g|png|webp)'
    foreach ($m in $rx2.Matches($Html)) {
        $path = Normalize-Url $m.Value
        [void]$set.Add(('https://www.centurycommunities.com{0}' -f $path))
    }
    return @($set)
}

function Score-Url([string]$Url, [string]$PlanSlug, [string]$PlanName) {
    $u = $Url.ToLowerInvariant()
    $slug = $PlanSlug.ToLowerInvariant()
    $name = (($PlanName -replace '^The\s+', '') -replace '\s+', '-').ToLowerInvariant()
    $score = 0
    if ($u -match 'icon-|interface-|footer|eliant|svg$|logo') { return -1000 }
    if ($u -match 'elev|elevation|exterior|ext_|model-photos|_ext') { $score += 50 }
    if ($u -match 'interior|furnished|kitchen|bedroom|great-room|primary') { $score += 20 }
    if ($slug -and $u.Contains($slug)) { $score += 80 }
    if ($name -and $u.Contains($name)) { $score += 60 }
    if ($u -match 'floor-\d|floorplan|floor_plan') { $score += 5 }
    if ($u -match 'aerial') { $score += 10 }
    return $score
}

function Pick-Images($Urls, [string]$PlanSlug, [string]$PlanName, [int]$Need = 4) {
    $ranked = @()
    foreach ($u in $Urls) {
        $s = Score-Url $u $PlanSlug $PlanName
        if ($s -gt 0) {
            $ranked += [pscustomobject]@{ Url = $u; Score = $s }
        }
    }
    $ranked = @($ranked | Sort-Object Score -Descending)
    $picked = New-Object System.Collections.Generic.List[string]
    $seenBase = New-Object 'System.Collections.Generic.HashSet[string]' ([StringComparer]::OrdinalIgnoreCase)
    foreach ($row in $ranked) {
        $base = [IO.Path]::GetFileNameWithoutExtension(([uri]$row.Url).AbsolutePath)
        if (-not $seenBase.Add($base)) { continue }
        $picked.Add($row.Url)
        if ($picked.Count -ge $Need) { break }
    }
    return @($picked)
}

function Download-File([string]$Url, [string]$Dest) {
    $dir = Split-Path -Parent $Dest
    if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
    $tmp = "$Dest.part"
    Invoke-WebRequest -Uri $Url -Headers @{ 'User-Agent' = $ua } -OutFile $tmp -UseBasicParsing -TimeoutSec 90
    Move-Item -Force -Path $tmp -Destination $Dest
    return (Get-Item $Dest).Length
}

$results = @()
$log = New-Object System.Collections.Generic.List[string]
$log.Add(("Started {0}" -f (Get-Date -Format o)))

foreach ($p in $inv.properties) {
    $slug = [string]$p.slug
    $planSlug = [string]$p.plan_slug
    $plan = [string]$p.plan
    $url = [string]$p.url
    Write-Host ("=== {0} ({1}) ===" -f $slug, $plan)
    $html = Get-Html $url
    $assets = Extract-AssetUrls $html
    Write-Host ("  assets found: {0}" -f $assets.Count)

    $picked = @(Pick-Images $assets $planSlug $plan 4)
    if ($picked.Count -lt 1) {
        $msg = "FAIL: no plan images for $slug"
        Write-Warning $msg
        $log.Add($msg)
        $results += [pscustomobject]@{
            slug = $slug
            plan = $plan
            ok = $false
            image_urls = @()
            error = 'no images extracted'
        }
        continue
    }

    $destDir = Join-Path $Root ("uploads\properties\{0}" -f $slug)
    $names = @('cover.jpg', '01.jpg', '02.jpg', '03.jpg')
    $saved = @()
    for ($i = 0; $i -lt $names.Count; $i++) {
        $src = if ($i -lt $picked.Count) { $picked[$i] } else { $picked[0] }
        $dest = Join-Path $destDir $names[$i]
        try {
            $bytes = Download-File $src $dest
            Write-Host ("  {0} <- {1} ({2} bytes)" -f $names[$i], $src, $bytes)
            $saved += @{ file = $names[$i]; url = $src; bytes = $bytes }
            $log.Add(("{0} {1} {2}" -f $slug, $names[$i], $src))
        } catch {
            $msg = ("FAIL download {0} {1}: {2}" -f $slug, $names[$i], $_.Exception.Message)
            Write-Warning $msg
            $log.Add($msg)
        }
    }

    $results += [pscustomobject]@{
        slug = $slug
        plan = $plan
        url = $url
        ok = ($saved.Count -ge 1)
        image_urls = @($picked)
        saved = $saved
    }
    Start-Sleep -Milliseconds 400
}

$coverHashes = Get-ChildItem -Path (Join-Path $Root 'uploads\properties\cc-*\cover.jpg') | Get-FileHash -Algorithm MD5
$groups = $coverHashes | Group-Object Hash | Sort-Object Count -Descending
$log.Add('--- cover hash groups ---')
foreach ($g in $groups) {
    $namesJoined = ($g.Group | ForEach-Object { Split-Path (Split-Path $_.Path -Parent) -Leaf }) -join ', '
    $line = ("{0}x {1} -> {2}" -f $g.Count, $g.Name, $namesJoined)
    $log.Add($line)
    Write-Host $line
}

$payload = [pscustomobject]@{
    generated = (Get-Date -Format o)
    unique_cover_hashes = @($groups | Where-Object { $_.Count -eq 1 }).Count
    duplicate_cover_groups = @($groups | Where-Object { $_.Count -gt 1 } | ForEach-Object {
        [pscustomobject]@{
            hash = $_.Name
            count = $_.Count
            slugs = @($_.Group | ForEach-Object { Split-Path (Split-Path $_.Path -Parent) -Leaf })
        }
    })
    properties = $results
}
$payload | ConvertTo-Json -Depth 8 | Set-Content -Path $OutMap -Encoding UTF8
$log | Set-Content -Path $LogPath -Encoding UTF8
Write-Host ("Wrote {0}" -f $OutMap)
Write-Host ("Unique covers: {0} / {1}" -f $payload.unique_cover_hashes, $results.Count)
