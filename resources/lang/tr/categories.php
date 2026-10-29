<?php

declare(strict_types=1);

return [
    /* kategori ağacı */
    'create' => 'Yeni kategori',
    'edit' => 'Kategoriyi düzenle',
    'parent' => 'Üst kategori',
    'parent_root' => '— Kök seviye —',
    'slug_segment' => 'URL parçası',
    'slug_segment_hint' => 'Yalnızca bu kategorinin kendi parçası. Noktalı tam yol üst kategorilerden otomatik oluşturulur; bir parçayı yeniden adlandırmak veya kategoriyi taşımak tüm alt kategori yollarını da günceller.',
    'slug_preview' => 'Tam yol',
    'image' => 'Kategori görseli',
    'image_path' => 'Görsel yolu',
    'image_upload_hint' => 'Yolu değiştirmek için yeni görsel yükleyin veya yolu doğrudan düzenleyin.',
    'icon' => 'Simge',
    'seo_title' => 'SEO başlığı',
    'sort_order' => 'Sıralama',
    'description' => 'Açıklama',
    'add_child' => 'Alt kategori ekle',
    'move_up' => 'Yukarı taşı',
    'move_down' => 'Aşağı taşı',
    'activate' => 'Etkinleştir',
    'deactivate' => 'Devre dışı bırak',
    'products_direct' => 'doğrudan',
    'products_subtree' => 'alt kategorilerle',
    'has_products_suffix' => '(ürün içeriyor — yaprak kalır)',
    'level_max_suffix' => '(3. seviye — en derin)',
    'delete_confirm' => 'Bu kategori silinsin mi?',
    'empty' => 'Henüz kategori yok.',

    /* koruma mesajları */
    'invalid_parent' => 'Seçilen üst kategori geçerli değil.',
    'depth_exceeded' => 'Kategoriler en fazla 3 seviye iç içe olabilir.',
    'parent_has_products' => 'Bu kategori doğrudan ürün içerdiği için alt kategori alamaz. Önce ürünlerini bir yaprak kategoriye taşıyın.',
    'slug_taken' => 'Bu kategori yolu mevcut bir yolla çakışıyor. Farklı bir URL parçası seçin.',
    'slug_required' => 'URL parçası — veya türetmek için İngilizce ad — gereklidir.',
    'delete_blocked_children' => 'Önce alt kategorileri silin veya taşıyın.',
    'delete_blocked_products' => 'Bu kategoride hâlâ ürün var. Önce onları başka bir kategoriye taşıyın.',

    /* koleksiyonlar */
    'collection_create' => 'Yeni koleksiyon',
    'collection_edit' => 'Koleksiyonu düzenle',
    'collection_in_use' => 'Bu koleksiyona hâlâ ürün bağlı. Önce onları yeniden atayın.',
    'collections_empty' => 'Henüz koleksiyon yok.',
    'collection_delete_confirm' => 'Bu koleksiyon silinsin mi?',

    /* markalar */
    'brand_create' => 'Yeni marka',
    'brand_edit' => 'Markayı düzenle',
    'brand_in_use' => 'Bu markaya hâlâ ürün bağlı. Önce onları yeniden atayın.',
    'brands_empty' => 'Henüz marka yok.',
    'brand_delete_confirm' => 'Bu marka silinsin mi?',
    'country' => 'Ülke',
    'website' => 'Web sitesi',
    'logo' => 'Logo',
    'logo_path' => 'Logo yolu',
];
