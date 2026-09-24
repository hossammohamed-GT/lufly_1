<?php

declare(strict_types=1);

return [
    'defaults' => [
        'title' => 'LUFLY | European Architectural Sanitary Ware Manufacturer & Global Export',
        'description' => 'LUFLY manufactures European certified architectural sanitary ware, rimless toilets, luxury PVD brassware, and hydrotherapy systems for premier residential and hospitality projects worldwide.',
        'keywords' => [
            'LUFLY',
            'sanitary ware manufacturer',
            'architectural fixtures',
            'luxury bathroom tapware',
            'PVD faucet finishes',
            'rimless ceramic toilets',
            'EN 997 certified sanitary',
            'vitreous china washbasins',
            'European export Gaziantep',
            'BIM CAD sanitary specifications',
            'mimari vitrifiye seramik',
            'asma klozet modelleri',
            'sanitarni keramika',
        ],
        'image' => '/images/lifestyle/heroc-1.webp',
        'site_name' => 'LUFLY Architectural Sanitary Ware',
        'twitter_handle' => '@LUFLYGlobal',
    ],

    'title_suffix' => ' | LUFLY',

    'structured_data' => [
        'organization' => true,
        'website' => true,
    ],

    'business' => [
        'legal_name' => 'LUFLY İNŞAAT SANAYİ VE TİCARET LİMİTED ŞİRKETİ',
        'brand_name' => 'LUFLY',
        'description' => 'European Architectural Sanitary Ware Manufacturer & Global Export',
        'street' => '',
        'city' => 'Gaziantep',
        'region' => 'Gaziantep',
        'postal_code' => '',
        'country' => 'TR',
        'telephone' => '+90-850-3040-817',
        'email' => 'info@lufly.tr',
        'latitude' => (float) env('SEO_GEO_LAT', '0'),
        'longitude' => (float) env('SEO_GEO_LNG', '0'),
        'same_as' => array_values(array_filter(array_map('trim', explode(',', (string) env('SEO_SOCIAL_PROFILES', ''))))),
    ],

    'enforce_host' => (bool) env('SEO_ENFORCE_HOST', false),

    'verification' => [
        'google' => (string) env('GOOGLE_SITE_VERIFICATION', ''),
        'bing' => (string) env('BING_SITE_VERIFICATION', ''),
    ],

    'analytics' => [
        'ga4' => (string) env('GA4_MEASUREMENT_ID', ''),     'gtm' => (string) env('GTM_CONTAINER_ID', ''),       'clarity' => (string) env('CLARITY_PROJECT_ID', ''),
    ],

    'sitemap_static_lastmod' => (string) env('SEO_STATIC_LASTMOD', date('Y-m-d')),
];
