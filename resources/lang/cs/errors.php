<?php

declare(strict_types=1);

return [
    'server_error' => 'Něco se pokazilo. Zkuste to prosím později.',
    'page_not_found_title' => 'Stránka nebyla nalezena',
    'page_not_found_message' => 'Stránka, kterou hledáte, mohla být přesunuta, přejmenována nebo nikdy neexistovala. Navedeme vás zpět.',
    'page_not_found_hint' => 'nebo pokračujte v prohlížení pomocí horní nabídky',
    'back_home' => 'Zpět na úvodní stránku',
    'browse_products' => 'Procházet katalog',
    'product_not_found' => 'Požadovaný produkt nebyl nalezen.',
    'user_not_found' => 'Požadovaný uživatel nebyl nalezen.',
    'media_not_found' => 'Požadovaná položka médií nebyla nalezena.',

    'upload_missing' => 'Nebyl nahrán žádný soubor.',
    'upload_too_large' => 'Soubor překračuje maximální velikost :max KB.',
    'upload_directory_failed' => 'Adresář pro nahrávání se nepodařilo vytvořit.',
    'upload_move_failed' => 'Nahraný soubor se nepodařilo uložit.',
    'upload_type_not_allowed' => 'Soubory s příponou ":extension" nejsou povoleny.',
    'upload_type_blocked' => 'Tento typ souboru je z bezpečnostních důvodů blokován.',
    'upload_php_error' => 'Nahrávání selhalo s PHP chybovým kódem :code.',

    'validation' => [
        'required' => 'Pole :attribute je povinné.',
        'email' => ':attribute musí být platná e-mailová adresa.',
        'string' => ':attribute musí být text.',
        'numeric' => ':attribute musí být číslo.',
        'integer' => ':attribute musí být celé číslo.',
        'boolean' => ':attribute musí být pravda nebo nepravda.',
        'min' => ':attribute musí být alespoň :param.',
        'max' => ':attribute nesmí být větší než :param.',
        'in' => 'Vybraná hodnota :attribute je neplatná.',
        'url' => ':attribute musí být platná adresa URL.',
        'date' => ':attribute musí být platné datum.',
        'array' => ':attribute musí být seznam.',
        'confirmed' => 'Potvrzení pole :attribute se neshoduje.',
        'unique' => ':attribute je již obsazeno.',
        'image' => ':attribute musí být obrázek.',
        'file' => ':attribute musí být nahraný soubor.',
        'mimes' => ':attribute musí být soubor typu: :param.',
    ],

    'attributes' => [
        'name' => 'název',
        'email' => 'e-mail',
        'password' => 'heslo',
        'price' => 'cena',
        'sku' => 'kód produktu',
        'status' => 'stav',
        'file' => 'soubor',
        'code' => 'kód',
        'title' => 'titulek',
    ],
    'not_found' => 'Požadovaný záznam nebyl nalezen.',
];
