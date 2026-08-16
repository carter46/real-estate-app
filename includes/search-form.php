<?php
/**
 * Homepage quick-search — Stitch overlapping bar structure.
 */

declare(strict_types=1);

$action = $searchAction ?? base_url('properties.php');
$location = (string) ($_GET['location'] ?? '');
$type = (string) ($_GET['type'] ?? '');
$price = (string) ($_GET['price'] ?? '');
?>
<form method="get" action="<?= e($action) ?>" class="grid grid-cols-1 md:grid-cols-4 gap-4 md:gap-6 items-end">
  <div>
    <label class="font-label-sm text-label-sm uppercase tracking-widest text-on-surface-variant mb-2 block" for="location">Location</label>
    <input id="location" name="location" value="<?= e($location) ?>" placeholder="Aspen, Vail, Beaver Creek..." class="w-full bg-transparent border-0 border-b border-on-surface focus:ring-0 focus:border-primary font-body-md text-body-md px-0 py-2"/>
  </div>
  <div>
    <label class="font-label-sm text-label-sm uppercase tracking-widest text-on-surface-variant mb-2 block" for="type">Property Type</label>
    <select id="type" name="type" class="w-full bg-transparent border-0 border-b border-on-surface focus:ring-0 focus:border-primary font-body-md text-body-md px-0 py-2">
      <option value="">All Types</option>
      <option value="single_family" <?= $type === 'single_family' ? 'selected' : '' ?>>Single Family</option>
      <option value="condo" <?= $type === 'condo' ? 'selected' : '' ?>>Condominium</option>
      <option value="estate" <?= $type === 'estate' ? 'selected' : '' ?>>Estate</option>
      <option value="chalet" <?= $type === 'chalet' ? 'selected' : '' ?>>Chalet</option>
      <option value="ranch" <?= $type === 'ranch' ? 'selected' : '' ?>>Ranch</option>
      <option value="land" <?= $type === 'land' ? 'selected' : '' ?>>Land</option>
    </select>
  </div>
  <div>
    <label class="font-label-sm text-label-sm uppercase tracking-widest text-on-surface-variant mb-2 block" for="price">Price Range</label>
    <select id="price" name="price" class="w-full bg-transparent border-0 border-b border-on-surface focus:ring-0 focus:border-primary font-body-md text-body-md px-0 py-2">
      <option value="">Any Price</option>
      <option value="2-5" <?= $price === '2-5' ? 'selected' : '' ?>>$2M–$5M</option>
      <option value="5-10" <?= $price === '5-10' ? 'selected' : '' ?>>$5M–$10M</option>
      <option value="10+" <?= $price === '10+' ? 'selected' : '' ?>>$10M+</option>
    </select>
  </div>
  <button type="submit" class="h-12 w-full md:w-12 bg-primary text-on-primary flex items-center justify-center hover:bg-primary-container transition-colors" aria-label="Search">
    <span class="material-symbols-outlined">search</span>
  </button>
</form>
