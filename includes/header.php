<?php
/**
 * Public header — Stitch/reference class structure, SDC branding.
 *
 * @var string|null $pageTitle
 * @var string|null $navVariant home|content
 * @var string|null $activeNav
 */

declare(strict_types=1);

$navVariant = $navVariant ?? 'home';
$activeNav = $activeNav ?? '';
require __DIR__ . '/stitch-head.php';
?>
<header class="fixed top-0 w-full z-50 bg-surface/90 backdrop-blur-md shadow-[0_1px_8px_rgba(55,5,24,0.04)]">
  <div class="h-20 max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop flex items-center justify-between">
    <div class="flex items-center gap-6">
      <a href="<?= e(base_url('index.php')) ?>" class="flex items-center gap-3 no-underline">
        <img src="<?= e(base_url('assets/img/logo-sdc.svg')) ?>" alt="Sunview Development and Consultancy (SDC)" class="h-10 w-auto object-contain"/>
      </a>
      <form action="<?= e(base_url('properties.php')) ?>" method="get" class="hidden lg:flex items-center bg-surface-container px-4 py-2 rounded-full border border-outline-variant/30 text-on-surface-variant">
        <span class="material-symbols-outlined text-[20px] mr-2">search</span>
        <input class="bg-transparent border-none outline-none text-label-sm w-48 placeholder:text-on-surface-variant/50 font-label-sm" placeholder="Search Colorado Properties..." name="q" type="search" value="<?= e((string) ($_GET['q'] ?? '')) ?>"/>
      </form>
    </div>
    <nav class="hidden xl:flex items-center gap-gutter" aria-label="Primary">
      <?php
      $link = function (string $key, string $label, string $href) use ($activeNav): void {
          $active = $activeNav === $key;
          $cls = $active
              ? 'transition-all py-1 text-primary font-bold border-b-2 border-primary font-subheading text-subheading'
              : 'font-subheading text-subheading text-on-surface-variant hover:text-primary transition-all py-1';
          echo '<a class="' . $cls . '" href="' . e($href) . '">' . e($label) . '</a>';
      };
      $link('buy', 'BUY', base_url('properties.php'));
      $link('sell', 'SELL', base_url('contact.php'));
      $link('rent', 'RENT', base_url('properties.php'));
      $link('luxury', 'LUXURY', base_url('properties.php'));
      if ($navVariant === 'home') {
          $link('commercial', 'COMMERCIAL', base_url('properties.php'));
      }
      $link('agents', 'AGENTS', base_url('agents.php'));
      if ($navVariant === 'content') {
          $link('about', 'ABOUT US', base_url('about.php'));
          $link('contact', 'CONTACT', base_url('contact.php'));
          $link('faq', 'FAQ', base_url('faq.php'));
      }
      ?>
    </nav>
    <div class="flex items-center gap-4">
      <a href="<?= e(base_url('contact.php')) ?>" class="hidden md:inline-flex font-label-sm text-label-sm px-6 py-2 border border-primary text-primary hover:bg-primary hover:text-on-primary transition-all no-underline uppercase tracking-widest">List With Us</a>
      <a href="<?= e(base_url('admin/login.php')) ?>" class="w-8 h-8 rounded-full bg-primary flex items-center justify-center hover:shadow-lg transition-shadow" aria-label="Account">
        <span class="material-symbols-outlined text-on-primary text-[18px]">person</span>
      </a>
      <details class="xl:hidden relative">
        <summary class="list-none cursor-pointer material-symbols-outlined text-[28px] text-primary">menu</summary>
        <div class="absolute right-0 mt-3 w-56 bg-surface border border-outline-variant/40 shadow-lg p-4 flex flex-col gap-3 z-50">
          <a class="font-subheading text-subheading uppercase tracking-widest" href="<?= e(base_url('properties.php')) ?>">Buy</a>
          <a class="font-subheading text-subheading uppercase tracking-widest" href="<?= e(base_url('contact.php')) ?>">Sell</a>
          <a class="font-subheading text-subheading uppercase tracking-widest" href="<?= e(base_url('agents.php')) ?>">Agents</a>
          <a class="font-subheading text-subheading uppercase tracking-widest" href="<?= e(base_url('about.php')) ?>">About</a>
          <a class="font-subheading text-subheading uppercase tracking-widest" href="<?= e(base_url('contact.php')) ?>">Contact</a>
          <a class="font-subheading text-subheading uppercase tracking-widest" href="<?= e(base_url('faq.php')) ?>">FAQ</a>
        </div>
      </details>
    </div>
  </div>
</header>
<main class="pt-20">
