<?php

declare(strict_types=1);

return [
    /* strom kategorií */
    'create' => 'Nová kategorie',
    'edit' => 'Upravit kategorii',
    'parent' => 'Nadřazená kategorie',
    'parent_root' => '— Kořenová úroveň —',
    'slug_segment' => 'Segment URL',
    'slug_segment_hint' => 'Pouze vlastní segment této kategorie. Celá tečkovaná cesta se skládá z nadřazených kategorií automaticky; přejmenování segmentu nebo přesun kategorie přepíše i cesty všech podkategorií.',
    'slug_preview' => 'Celá cesta',
    'image' => 'Obrázek kategorie',
    'image_path' => 'Cesta k obrázku',
    'image_upload_hint' => 'Nahrajte nový obrázek, nebo cestu upravte přímo.',
    'icon' => 'Ikona',
    'seo_title' => 'SEO titulek',
    'sort_order' => 'Pořadí',
    'description' => 'Popis',
    'add_child' => 'Přidat podkategorii',
    'move_up' => 'Posunout výš',
    'move_down' => 'Posunout níž',
    'activate' => 'Aktivovat',
    'deactivate' => 'Deaktivovat',
    'products_direct' => 'přímo',
    'products_subtree' => 'včetně podkategorií',
    'has_products_suffix' => '(obsahuje produkty — zůstává listem)',
    'level_max_suffix' => '(úroveň 3 — nejhlubší)',
    'delete_confirm' => 'Smazat tuto kategorii?',
    'empty' => 'Zatím žádné kategorie.',

    /* ochranné zprávy */
    'invalid_parent' => 'Zvolená nadřazená kategorie není platná.',
    'depth_exceeded' => 'Kategorie lze vnořit maximálně do 3 úrovní.',
    'parent_has_products' => 'Tato kategorie obsahuje produkty přímo, proto nemůže získat podkategorie. Nejprve její produkty přesuňte do listové kategorie.',
    'slug_taken' => 'Tato cesta kategorie koliduje s existující. Zvolte jiný segment URL.',
    'slug_required' => 'Segment URL — nebo anglický název, ze kterého se odvodí — je povinný.',
    'delete_blocked_children' => 'Nejprve smažte nebo přesuňte podkategorie.',
    'delete_blocked_products' => 'Tato kategorie stále obsahuje produkty. Nejprve je přesuňte do jiné kategorie.',

    /* kolekce */
    'collection_create' => 'Nová kolekce',
    'collection_edit' => 'Upravit kolekci',
    'collection_in_use' => 'K této kolekci stále patří produkty. Nejprve je přeřaďte.',
    'collections_empty' => 'Zatím žádné kolekce.',
    'collection_delete_confirm' => 'Smazat tuto kolekci?',

    /* značky */
    'brand_create' => 'Nová značka',
    'brand_edit' => 'Upravit značku',
    'brand_in_use' => 'K této značce stále patří produkty. Nejprve je přeřaďte.',
    'brands_empty' => 'Zatím žádné značky.',
    'brand_delete_confirm' => 'Smazat tuto značku?',
    'country' => 'Země',
    'website' => 'Web',
    'logo' => 'Logo',
    'logo_path' => 'Cesta k logu',
];
