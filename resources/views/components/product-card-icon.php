<?php
/**
 * Small stroke icon for product-card features and quick specifications.
 * Backed by ProductFeatureIconCatalog so server cards, live search, and
 * future Admin Dashboard icon pickers share one canonical icon set.
 *
 * @var array{name?: string} $props
 */
$name = (string) ($props['name'] ?? 'spec');
echo \Modules\Products\Services\ProductFeatureIconCatalog::svg($name);
