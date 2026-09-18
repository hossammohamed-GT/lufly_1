<?php

declare(strict_types=1);

return [
    'server_error' => 'Bir sorun oluştu. Lütfen daha sonra tekrar deneyin.',
    'product_not_found' => 'İstenen ürün bulunamadı.',
    'user_not_found' => 'İstenen kullanıcı bulunamadı.',
    'media_not_found' => 'İstenen medya öğesi bulunamadı.',

    'upload_missing' => 'Herhangi bir dosya yüklenmedi.',
    'upload_too_large' => 'Dosya, en fazla :max KB olan boyut sınırını aşıyor.',
    'upload_directory_failed' => 'Yükleme dizini oluşturulamadı.',
    'upload_move_failed' => 'Yüklenen dosya kaydedilemedi.',
    'upload_type_not_allowed' => '":extension" uzantılı dosyalara izin verilmiyor.',
    'upload_type_blocked' => 'Bu dosya türü güvenlik nedeniyle engellenmiştir.',
    'upload_php_error' => 'Yükleme, PHP hata kodu :code ile başarısız oldu.',

    'validation' => [
        'required' => ':attribute alanı zorunludur.',
        'email' => ':attribute geçerli bir e-posta adresi olmalıdır.',
        'string' => ':attribute metin olmalıdır.',
        'numeric' => ':attribute sayı olmalıdır.',
        'integer' => ':attribute tam sayı olmalıdır.',
        'boolean' => ':attribute doğru ya da yanlış olmalıdır.',
        'min' => ':attribute en az :param olmalıdır.',
        'max' => ':attribute en fazla :param olmalıdır.',
        'in' => 'Seçilen :attribute geçersiz.',
        'url' => ':attribute geçerli bir URL olmalıdır.',
        'date' => ':attribute geçerli bir tarih olmalıdır.',
        'array' => ':attribute bir liste olmalıdır.',
        'confirmed' => ':attribute onayı eşleşmiyor.',
        'unique' => ':attribute zaten kullanılıyor.',
        'image' => ':attribute bir görsel olmalıdır.',
        'file' => ':attribute yüklenmiş bir dosya olmalıdır.',
        'mimes' => ':attribute şu türde bir dosya olmalıdır: :param.',
    ],

    'attributes' => [
        'name' => 'ad',
        'email' => 'e-posta',
        'password' => 'şifre',
        'price' => 'fiyat',
        'sku' => 'stok kodu',
        'status' => 'durum',
        'file' => 'dosya',
        'code' => 'kod',
        'title' => 'başlık',
    ],
    'not_found' => 'İstenen kayıt bulunamadı.',
];
