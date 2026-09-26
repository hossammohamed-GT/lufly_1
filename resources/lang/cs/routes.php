<?php

declare(strict_types=1);

return [
    'home' => '',
    'products.index' => 'produkty',
    'products.show' => 'produkty/{slug}',
    'login' => 'prihlaseni',
    'contact' => 'kontakt',
    'favorites.index' => 'oblibene',
    'favorites.claim' => 'oblibene/{token}',
    'favorites.toggle' => 'oblibene/ulozit',
    'favorites.email' => 'oblibene/email',
    'favorites.clear' => 'oblibene/vymazat',

    /* quotation box */
    'box.index' => 'krabice',
    'box.claim' => 'krabice/{token}',
    'box.add' => 'krabice/pridat',
    'box.remove' => 'krabice/odebrat',
    'box.clear' => 'krabice/vysypat',
    'box.send' => 'krabice/poslat',

    /* bathroom planner */
    'planner.index' => 'planovac-koupelny',
    'planner.step' => 'planovac-koupelny/krok',
    'planner.fit' => 'planovac-koupelny/vejde-se',
    'planner.render' => 'planovac-koupelny/obrazek',
    'planner.send' => 'planovac-koupelny/odeslat',

    /* vyhledávač výrobků */
    'assistant.ask' => 'asistent/najdi',
];
