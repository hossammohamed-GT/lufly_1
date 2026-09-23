<?php

declare(strict_types=1);

return [
    /* page */
    'eyebrow' => 'LUFLY · kayıtlı ürünler',
    'title' => 'Kayıtlı listeniz',
    'lede' => 'İşaretlediğiniz her ürün tek bir yerde. Listeyi kendinize e-posta ile gönderin, ürünü doğrudan mesajdan açın ya da bu sayfaya istediğiniz zaman geri dönün.',
    'meta_description' => 'Kaydettiğiniz LUFLY ürünleri tek listede — kendinize e-posta gönderin, her ürünü doğrudan açın ve listeye dilediğiniz zaman dönün.',

    'stat_items' => 'kayıtlı ürün',
    'stat_language' => 'dil',
    'stat_access' => 'erişim',
    'stat_access_value' => 'üyelik yok',

    'list_title' => 'Kayıtlı ürünler',
    'list_count_one' => 'Bir ürün',
    'list_count_many' => ':n ürün',
    'open_product' => 'Ürünü aç',
    'remove' => 'Kaldır',
    'save' => 'Kaydet',
    'saved' => 'Kaydedildi',
    'save_to_list' => 'Listeme kaydet',
    'added_named' => ':name kayıtlı listenize eklendi.',
    'removed_named' => ':name kayıtlı listenizden çıkarıldı.',
    'cleared' => 'Kayıtlı listeniz boş.',

    /* empty state */
    'empty_title' => 'Henüz kayıt yok',
    'empty_text' => 'Kataloğa göz atın ve beğendiğiniz üründe kalbe dokunun. Burada kalır — dilerseniz tüm listeyi size e-posta ile göndeririz.',
    'empty_cta' => 'Kataloğa göz atın',
    'step_1_title' => 'Kaydedin',
    'step_1_text' => 'Katalog kartındaki veya ürün sayfasındaki kalbe dokunun.',
    'step_2_title' => 'Geri dönün',
    'step_2_text' => 'Liste burada sizi bekler ve bu cihaza bağlı kalır.',
    'step_3_title' => 'E-posta gönderin',
    'step_3_text' => 'Listeyi kendinize gönderin — her ürün kendi sayfasına doğrudan bağlantı taşır.',

    /* mail panel */
    'mail_eyebrow' => 'Listeyi gönder',
    'mail_title' => 'Bu listeyi bana e-posta ile gönder',
    'mail_text_one' => 'Kaydettiğiniz ürünü ve ürün sayfasına doğrudan bağlantıyı içeren tek bir mesaj.',
    'mail_text_many' => ':n kayıtlı ürün ve her biri için doğrudan bağlantı içeren tek bir mesaj.',
    'mail_placeholder' => 'siz@firma.com',
    'mail_button' => 'Listemi e-posta ile gönder',
    'notify_label' => 'Yeni bir ürün kaydettiğimde bana e-posta gönder',
    'notify_hint' => 'Gönderdiğimiz tek mesaj bu — buradan istediğiniz zaman kapatabilirsiniz.',
    'mail_sending' => 'Gönderiliyor…',
    'mail_sent' => 'Liste :email adresine gönderiliyor.',
    'mail_point_1' => 'Her ürün kendi doğrudan bağlantısıyla',
    'mail_point_2' => 'Liste her cihazda yeniden açılır',
    'mail_point_3' => 'Üyelik yok, şifre yok',

    /* save-to-mail prompt */
    'prompt_title' => 'Bu ürünü kendinize e-posta ile gönderin',
    'prompt_text' => 'Kaydettiğiniz her şeye doğrudan bağlantı içeren tek bir mesaj; gelen kutunuzda her cihazdan açmaya hazır.',
    'prompt_button' => 'Bana gönder',
    'prompt_sending' => 'Gönderiliyor…',
    'prompt_note' => 'Hesap gerekmez — bu adresi yalnızca bu liste için kullanıyoruz.',
    'prompt_close' => 'Kapat',

    /* permanent link */
    'link_title' => 'Kalıcı bağlantınız',
    'link_text' => 'Bu adres her zaman bu listeyi açar — yer imlerinize ekleyin veya bir iş ortağınıza gönderin.',
    'copy' => 'Bağlantıyı kopyala',
    'copied' => 'Bağlantı kopyalandı.',
    'copy_failed' => 'Adresi elle kopyalayın.',

    'clear_button' => 'Listeyi temizle',
    'clear_confirm' => 'Kayıtlı listedeki tüm ürünler çıkarılsın mı?',

    'help_title' => 'Fiyat veya BIM paketi mi gerekiyor?',
    'help_text' => 'Listeyi bize gönderin, proje fiyat teklifiyle dönelim.',
    'help_cta' => 'Fabrikayla konuşun',

    /* errors */
    'err_email' => 'Lütfen geçerli bir e-posta adresi girin.',
    'err_empty' => 'Listeyi göndermeden önce bir ürün kaydedin.',
    'err_product' => 'Bu ürün artık mevcut değil.',
    'err_cooldown' => 'Liste az önce gönderildi — :s saniye sonra tekrar deneyin.',
    'err_daily_limit' => 'Liste bugün :n kez gönderildi. Lütfen yarın tekrar deneyin.',
    'err_send' => 'Mesaj gönderilemedi. Lütfen biraz sonra tekrar deneyin.',
    'limit_reached' => 'Kayıtlı liste en fazla :n ürün alır. Yenisini eklemek için birini çıkarın.',
    'expired' => 'Oturumunuz zaman aşımına uğradı — sayfayı yenileyip tekrar deneyin.',

    'not_found' => 'Bu kayıtlı liste bulunamadı.',

    /* e-mail */
    'mail_subject_one' => 'LUFLY kayıtlı listeniz — 1 ürün',
    'mail_subject_many' => 'LUFLY kayıtlı listeniz — :n ürün',
    'mail_admin_subject_one' => ':email adresinden kayıtlı liste (1 ürün)',
    'mail_admin_subject_many' => ':email adresinden kayıtlı liste (:n ürün)',
    'mail_preheader_one' => 'Doğrudan bağlantılı 1 kayıtlı LUFLY ürünü.',
    'mail_preheader_many' => ':n kayıtlı LUFLY ürünü, her biri doğrudan bağlantılı.',
    'mail_intro_one' => 'lufly.tr üzerinde kaydettiğiniz ürün — aşağıdaki düğme kendi sayfasını açar.',
    'mail_intro_many' => 'lufly.tr üzerinde kaydettiğiniz liste: :n ürün, her biri kendi sayfasına bağlantılı.',
    'mail_open_product' => 'Ürünü görün',
    'mail_direct_link' => 'ürün sayfasını açar',
    'mail_list_title' => 'Listeniz her zaman elinizin altında',
    'mail_list_hint' => 'Aşağıdaki bağlantı aynı listeyi her cihazda açar — telefon, tablet veya bir iş arkadaşınızın bilgisayarı.',
    'mail_list_button' => 'Listemi aç',
    'mail_why' => 'Bu e-postayı LUFLY kayıtlı listenizi istediğiniz için aldınız.',
    'mail_contact' => 'Fiyat, soru veya BIM paketi için bu e-postayı yanıtlamanız yeterli.',
    'admin_eyebrow' => 'kayıtlı liste · talep',
    'mail_admin_intro' => 'Bir ziyaretçi bu ürünleri kaydetti ve bir kopyasını istedi',
    'mail_admin_meta_one' => 'Listede 1 ürün var',
    'mail_admin_meta_many' => 'Listede :n ürün var',
    'admin_visitor' => 'Ziyaretçi:',
    'admin_product' => 'Ürün',
    'admin_model' => 'Model',
    'admin_open' => 'Aç',
    'admin_reply_hint' => 'Bu mesajı yanıtladığınızda doğrudan ziyaretçiye ulaşır.',
];
