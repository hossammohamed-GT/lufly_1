<?php

declare(strict_types=1);

return [
    'home' => '',
    'products.index' => 'products',
    'products.show' => 'products/{slug}',
    'login' => 'login',
    'contact' => 'contact',
    'favorites.index' => 'favorites',
    'favorites.claim' => 'favorites/{token}',
    'favorites.toggle' => 'favorites/toggle',
    'favorites.email' => 'favorites/email',
    'favorites.clear' => 'favorites/clear',

    'box.index' => 'box',
    'box.claim' => 'box/{token}',
    'box.add' => 'box/add',
    'box.remove' => 'box/remove',
    'box.clear' => 'box/clear',
    'box.send' => 'box/send',

    'box.index' => 'box',
    'box.claim' => 'box/{token}',
    'box.add' => 'box/add',
    'box.remove' => 'box/remove',
    'box.clear' => 'box/clear',
    'box.send' => 'box/send',

    'planner.index' => 'planner',
    'planner.step' => 'planner/step',
    'planner.fit' => 'planner/fit',
    'planner.render' => 'planner/picture',
    'planner.send' => 'planner/send',

    'assistant.ask' => 'assistant/find',
];
