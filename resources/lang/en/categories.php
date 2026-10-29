<?php

declare(strict_types=1);

return [
    /* category tree */
    'create' => 'New category',
    'edit' => 'Edit category',
    'parent' => 'Parent category',
    'parent_root' => '— Root level —',
    'slug_segment' => 'URL segment',
    'slug_segment_hint' => 'Only this category\'s own segment. The full dotted path is built from its parents automatically; renaming a segment or moving a category rewrites every sub-category path too.',
    'slug_preview' => 'Full path',
    'image' => 'Category image',
    'image_path' => 'Image path',
    'image_upload_hint' => 'Upload a new image to replace the path, or edit the path directly.',
    'icon' => 'Icon',
    'seo_title' => 'SEO title',
    'sort_order' => 'Sort order',
    'description' => 'Description',
    'add_child' => 'Add sub-category',
    'move_up' => 'Move up',
    'move_down' => 'Move down',
    'activate' => 'Activate',
    'deactivate' => 'Deactivate',
    'products_direct' => 'direct',
    'products_subtree' => 'with sub-categories',
    'has_products_suffix' => '(holds products — stays a leaf)',
    'level_max_suffix' => '(level 3 — deepest)',
    'delete_confirm' => 'Delete this category?',
    'empty' => 'No categories yet.',

    /* guard messages */
    'invalid_parent' => 'The chosen parent category is not valid.',
    'depth_exceeded' => 'Categories can only nest 3 levels deep.',
    'parent_has_products' => 'This category holds products directly, so it cannot gain sub-categories. Move its products to a leaf category first.',
    'slug_taken' => 'That category path collides with an existing one. Choose a different URL segment.',
    'slug_required' => 'A URL segment — or an English name to derive it from — is required.',
    'delete_blocked_children' => 'Delete or move the sub-categories first.',
    'delete_blocked_products' => 'This category still holds products. Move them to another category first.',

    /* collections */
    'collection_create' => 'New collection',
    'collection_edit' => 'Edit collection',
    'collection_in_use' => 'Products still belong to this collection. Reassign them first.',
    'collections_empty' => 'No collections yet.',
    'collection_delete_confirm' => 'Delete this collection?',

    /* brands */
    'brand_create' => 'New brand',
    'brand_edit' => 'Edit brand',
    'brand_in_use' => 'Products still belong to this brand. Reassign them first.',
    'brands_empty' => 'No brands yet.',
    'brand_delete_confirm' => 'Delete this brand?',
    'country' => 'Country',
    'website' => 'Website',
    'logo' => 'Logo',
    'logo_path' => 'Logo path',
];
