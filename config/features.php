<?php

declare(strict_types=1);

return [
    // B2B Wholesale quotation & WhatsApp inquiry workflow
    'b2b_inquiry' => (bool) env('FEATURE_B2B_INQUIRY', true),

    // AI helpers (Gemini key pool): the bathroom planner, the assistant and
    // (later) the product finder. See config/ai.php.
    'ai' => (bool) env('FEATURE_AI', true),

    // Hide prices by default for luxury sanitary B2B export pricing
    'show_prices' => (bool) env('FEATURE_SHOW_PRICES', false),

    // Multi-Language architecture (EN, TR, AR, CS)
    'multilingual' => (bool) env('FEATURE_MULTILINGUAL', true),

    // Light / Dark design system theme switcher
    'theme_switcher' => (bool) env('FEATURE_THEME_SWITCHER', true),

    // Export & download technical product specification sheets
    'specs_download' => (bool) env('FEATURE_SPECS_DOWNLOAD', true),

    // Quick view modal for products in catalog
    'quick_view' => (bool) env('FEATURE_QUICK_VIEW', true),

    // Saved-products lists ("favorites"): anonymous lists, a return link and
    // the e-mailed copy of everything the visitor saved (see config/favorites.php)
    'favorites' => (bool) env('FEATURE_FAVORITES', true),

    // The quotation box: a second, shareable list a visitor fills with the pieces
    // he wants priced, and sends to the team in one message (see config/box.php)
    'box' => (bool) env('FEATURE_BOX', true),

    // Bathroom planner: three taps → a sized plan, a scaled drawing and the
    // matching products (see config/planner.php)
    'planner' => (bool) env('FEATURE_PLANNER', true),

    // The optional picture of the finished bathroom (one image per visitor
    // per plan, kept inside a small daily allowance)
    'render' => (bool) env('FEATURE_PLANNER_RENDER', true),
];
