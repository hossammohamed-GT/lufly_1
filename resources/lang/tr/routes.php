<?php

declare(strict_types=1);

return [
    'home' => '',
    'products.index' => 'urunler',
    'products.show' => 'urunler/{slug}',
    'login' => 'giris',
    'contact' => 'iletisim',
    'favorites.index' => 'favoriler',
    'favorites.claim' => 'favoriler/{token}',
    'favorites.toggle' => 'favoriler/kaydet',
    'favorites.email' => 'favoriler/e-posta',
    'favorites.clear' => 'favoriler/temizle',

    /* bathroom planner */
    'planner.index' => 'banyo-planlayici',
    'planner.step' => 'banyo-planlayici/adim',
    'planner.fit' => 'banyo-planlayici/uyar-mi',
    'planner.render' => 'banyo-planlayici/gorsel',
    'planner.send' => 'banyo-planlayici/gonder',
];
