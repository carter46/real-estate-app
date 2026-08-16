<?php
/**
 * Stitch-matched head assets (Tailwind CDN + reference tokens).
 * Source of truth: references Stitch code.html Tailwind config (not DESIGN.md prose).
 * Compiled/local CSS can replace CDN later if desired (see DEPLOY.md section 10).
 *
 * @var string|null $pageTitle
 */

declare(strict_types=1);

$pageTitle = $pageTitle ?? site_name();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?= e($pageTitle) ?></title>
<?php
$favicon = site_favicon_url();
if ($favicon !== ''):
?>
<link rel="icon" href="<?= e($favicon) ?>"/>
<?php endif; ?>
<style>
@layer base {
  html, body { margin: 0; padding: 0; }
  body { overscroll-behavior: none; }
  main > :first-child { margin-top: 0 !important; }
  main > :last-child { margin-bottom: 0 !important; }
}
::-webkit-scrollbar { display: none; }
.material-symbols-outlined {
  font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24;
}
body:has(#site-nav:checked) {
  overflow: hidden;
}
#site-nav:checked ~ aside[aria-label="Mobile navigation"] {
  transform: translateX(0);
}
#site-nav:checked ~ label[for="site-nav"].fixed {
  opacity: 1;
  pointer-events: auto;
}
</style>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        "background": "#fcf9f8",
        "surface": "#fcf9f8",
        "error-container": "#ffdad6",
        "on-tertiary-fixed-variant": "#454747",
        "primary-container": "#521a2d",
        "on-error-container": "#93000a",
        "primary-fixed": "#ffd9e1",
        "surface-variant": "#e5e2e1",
        "on-primary-fixed-variant": "#713246",
        "tertiary-fixed": "#e2e2e2",
        "surface-container-low": "#f6f3f2",
        "on-secondary-fixed-variant": "#474743",
        "surface-container-highest": "#e5e2e1",
        "on-tertiary": "#ffffff",
        "surface-dim": "#dcd9d9",
        "surface-container-lowest": "#ffffff",
        "primary": "#370518",
        "tertiary-container": "#2c2e2e",
        "on-background": "#1c1b1b",
        "tertiary": "#17191a",
        "on-secondary-fixed": "#1c1c18",
        "on-surface-variant": "#524346",
        "outline-variant": "#d7c1c5",
        "surface-tint": "#8d495d",
        "surface-bright": "#fcf9f8",
        "on-primary": "#ffffff",
        "surface-container-high": "#eae7e7",
        "on-error": "#ffffff",
        "inverse-on-surface": "#f3f0ef",
        "secondary-fixed": "#e5e2dc",
        "outline": "#847376",
        "on-secondary": "#ffffff",
        "on-primary-container": "#cd7f94",
        "on-secondary-container": "#656460",
        "tertiary-fixed-dim": "#c6c6c7",
        "on-primary-fixed": "#3a071b",
        "on-tertiary-container": "#949596",
        "inverse-primary": "#ffb1c5",
        "secondary-container": "#e5e2dc",
        "secondary-fixed-dim": "#c9c6c1",
        "secondary": "#5f5e5a",
        "primary-fixed-dim": "#ffb1c5",
        "inverse-surface": "#313030",
        "on-surface": "#1c1b1b",
        "surface-container": "#f0eded",
        "error": "#ba1a1a",
        "on-tertiary-fixed": "#1a1c1c"
      },
      borderRadius: { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
      spacing: {
        "section-gap": "120px",
        "unit": "8px",
        "container-max": "1440px",
        "gutter": "24px",
        "margin-desktop": "64px",
        "margin-mobile": "20px"
      },
      fontFamily: {
        "label-sm": ["Montserrat"],
        "display-lg": ["Libre Caslon Text"],
        "body-md": ["Montserrat"],
        "display-lg-mobile": ["Libre Caslon Text"],
        "body-lg": ["Montserrat"],
        "subheading": ["Montserrat"],
        "headline-md": ["Libre Caslon Text"]
      },
      fontSize: {
        "label-sm": ["12px", { "lineHeight": "1.2", "letterSpacing": "0.05em", "fontWeight": "600" }],
        "display-lg": ["64px", { "lineHeight": "1.1", "letterSpacing": "-0.02em", "fontWeight": "400" }],
        "body-md": ["16px", { "lineHeight": "1.6", "fontWeight": "400" }],
        "display-lg-mobile": ["40px", { "lineHeight": "1.2", "fontWeight": "400" }],
        "body-lg": ["18px", { "lineHeight": "1.6", "fontWeight": "300" }],
        "subheading": ["14px", { "lineHeight": "1.5", "letterSpacing": "0.1em", "fontWeight": "600" }],
        "headline-md": ["32px", { "lineHeight": "1.3", "fontWeight": "400" }]
      }
    }
  }
}
</script>
<link href="https://fonts.googleapis.com/css2?family=Libre+Caslon+Text:ital,wght@0,400;1,400&amp;family=Montserrat:wght@300;400;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&amp;display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="<?= e(base_url('assets/css/public-overrides.css')) ?>"/>
</head>
<body class="bg-background font-body-md text-on-surface">
