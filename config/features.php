<?php

declare(strict_types=1);

return [

    'b2b_inquiry' => (bool) env('FEATURE_B2B_INQUIRY', true),


    'ai' => (bool) env('FEATURE_AI', true),

    'show_prices' => (bool) env('FEATURE_SHOW_PRICES', false),

    'multilingual' => (bool) env('FEATURE_MULTILINGUAL', true),

    'theme_switcher' => (bool) env('FEATURE_THEME_SWITCHER', true),

    'specs_download' => (bool) env('FEATURE_SPECS_DOWNLOAD', true),

    'quick_view' => (bool) env('FEATURE_QUICK_VIEW', true),


    'favorites' => (bool) env('FEATURE_FAVORITES', true),


    'box' => (bool) env('FEATURE_BOX', true),


    'planner' => (bool) env('FEATURE_PLANNER', true),


    'render' => (bool) env('FEATURE_PLANNER_RENDER', true),
];
