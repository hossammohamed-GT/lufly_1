<?php

declare(strict_types=1);

namespace Modules\Assistant\Services;

/**
 * What the chat says when there is no model to ask.
 *
 * The model is the real brain of this chat, but a shop can never answer a visitor
 * with silence because a key ran out, the quota is spent or the network blinked.
 * The things people actually say to a chat — hello, thanks, "do you speak
 * Arabic?", "where are you?", "how much is it?", "do you deliver?" — are answered
 * here, in the visitor's own language, from a short table.
 *
 * It is also the layer that keeps the chat honest: a question about the weather or
 * the football is not the shop's business, and it says so in one friendly line
 * instead of dragging the visitor towards a shelf of washbasins.
 *
 * Nothing here searches the catalogue and nothing here costs anything.
 */
final class Talk
{
    /** Languages the shop answers in. The storefront ships en/tr/cs; Arabic is for the visitor who writes Arabic. */
    private const LANGUAGES = ['en', 'tr', 'cs', 'ar'];

    /**
     * Words that put a sentence inside the shop's world. A question that holds
     * none of them is answered as small talk instead of as a search.
     */
    private const DOMAIN = [
        /* the shop's own words. Written as stems on purpose: "koupelnové" and
           "koupelna" are the same room, and the test is a plain substring one. */
        'bathroom', 'bath', 'kitchen', 'washbasin', 'basin', 'sink', 'toilet', 'wc', 'shower', 'tap',
        'mixer', 'faucet', 'ceramic', 'tile', 'mirror', 'cabinet', 'furniture', 'radiator', 'accessor',
        'price', 'cost', 'quote', 'deliver', 'shipping', 'ship', 'order', 'install', 'fitting',
        'size', 'measure', 'material', 'factory', 'wholesale', 'export', 'container', 'catalog',
        'stock', 'available', 'sample', 'moq', 'port', 'sanitary',
        'banyo', 'mutfak', 'lavabo', 'klozet', 'duş', 'dus', 'musluk', 'batarya', 'seramik', 'karo',
        'ayna', 'dolap', 'fiyat', 'ücret', 'ucret', 'teslimat', 'kargo', 'sipariş', 'siparis', 'montaj',
        'ölçü', 'olcu', 'malzeme', 'fabrika', 'toptan', 'ihracat', 'konteyner', 'katalog', 'stok', 'numune',
        'koupel', 'kuchyn', 'umyvadl', 'zachod', 'toalet', 'sprch', 'bateri', 'keramik', 'obklad', 'zrcadl',
        'skrink', 'cena', 'cen', 'doprav', 'dodan', 'objedna', 'montaz', 'rozměr', 'rozmer', 'materiál',
        'tovarn', 'velkoobchod', 'kontejner', 'sklad', 'vzorek', 'doplňk', 'doplnk',
        'حمام', 'بانيو', 'مطبخ', 'حوض', 'احواض', 'أحواض', 'مغسلة', 'قاعدة', 'مرحاض', 'تواليت', 'دش', 'شاور',
        'خلاط', 'حنفية', 'سيراميك', 'بورسلين', 'بلاط', 'مرايا', 'خزائن', 'اكسسوار', 'إكسسوار', 'ادوات صحية',
        'سعر', 'اسعار', 'أسعار', 'تكلفة', 'توصيل', 'شحن', 'اوردر', 'أوردر', 'طلب', 'تركيب', 'مقاس',
        'سنتيمتر', 'خامة', 'مصنع', 'جملة', 'تصدير', 'كونتينر', 'كتالوج', 'مخزون', 'متوفر', 'عينة',
    ];

    /** "Do you speak Arabic?" — the visitor asks about the language itself. */
    private const LANGUAGE_ASK = [
        'arabic', 'arab', 'speak arabic', 'in arabic', 'turkish', 'turkce', 'türkçe', 'czech', 'cesky', 'česky',
        'english', 'speak', 'language', 'dil', 'diliniz', 'jazyk', 'mluvite', 'arabca', 'arapca', 'arapça',
        'بالعربي', 'بالعربى', 'عربي', 'عربى', 'العربية', 'لغة', 'لغه', 'بتتكلم', 'تتكلم', 'تتكلمي', 'بتحكي',
        'تركي', 'تركى', 'انجليزي', 'إنجليزي', 'تشيكي',
    ];

    private const GREET = [
        'hello', 'hi', 'hey', 'good morning', 'good evening', 'salam', 'assalam', 'merhaba', 'selam', 'ahoj',
        'dobry den', 'zdravim', 'zdravím', 'مرحبا', 'مرحبتين', 'اهلا', 'أهلا', 'اهلا وسهلا', 'السلام عليكم',
        'سلام عليكم', 'هاي', 'هلا', 'صباح الخير', 'مساء الخير',
    ];

    private const THANKS = [
        'thanks', 'thank you', 'thx', 'cheers', 'tesekkur', 'teşekkür', 'sagol', 'sağol', 'diky', 'díky',
        'dekuji', 'děkuji', 'شكرا', 'شكرًا', 'متشكر', 'تسلم', 'ربنا يكرمك', 'جزاك الله',
    ];

    /** "Who are you?" / "what do you sell?" */
    private const WHO = [
        'who are you', 'what are you', 'your name', 'what do you sell', 'what do you make', 'what is this',
        'about you', 'about the shop', 'about lufly', 'kimsiniz', 'nesiniz', 'ne satiyorsunuz', 'ne satıyorsunuz',
        'kdo jste', 'co prodavate', 'co prodáváte', 'من انت', 'من أنت', 'انت مين', 'أنت مين', 'بتعملوا ايه',
        'بتعمل ايه', 'بتصنعوا ايه', 'عندكم ايه', 'الموقع ده', 'ايه ده',
    ];

    /** "Where are you?" / contact */
    private const WHERE = [
        'where are you', 'where is the factory', 'your address', 'contact', 'phone', 'telephone', 'email',
        'adres', 'nerede', 'iletisim', 'iletişim', 'telefon', 'adresa', 'kontakt', 'kde jste',
        'فين', 'عنوانكم', 'العنوان', 'تليفون', 'رقم', 'تواصل', 'اميل', 'ايميل', 'فين المصنع', 'مقركم',
    ];

    /** Delivery / shipping / ordering */
    private const SHIPPING = [
        'deliver', 'delivery', 'shipping', 'ship', 'send it', 'order', 'when can i get', 'how long',
        'teslimat', 'kargo', 'gonderi', 'gönderi', 'siparis', 'sipariş', 'ne zaman', 'doprava', 'dodani',
        'dodání', 'objednat', 'kdy', 'توصيل', 'شحن', 'هتوصل', 'امتى', 'متى', 'الاوردر', 'أوردر', 'الشحن',
    ];

    /** Price */
    private const PRICE = [
        'price', 'how much', 'cost', 'quote', 'discount', 'fiyat', 'ne kadar', 'kac para', 'kaç para', 'tutar',
        'cena', 'kolik', 'sleva', 'بكام', 'بكم', 'السعر', 'سعر', 'اسعار', 'أسعار', 'كام', 'الخصم', 'تخفيض',
    ];

    /**
     * One line per language and intent. Short on purpose: a chat that writes a
     * paragraph is a chat nobody reads.
     *
     * @var array<string, array<string, array{say:string, note:string}>>
     */
    private const LINES = [
        'en' => [
            'language' => ['say' => 'Yes — say it in Arabic and I answer in Arabic (أهلاً بيك 👋). Turkish and Czech too.', 'note' => 'Ask me whatever you like: sizes, installation, materials — or the piece you cannot find.'],
            'greet' => ['say' => 'Hello! I am the LUFLY assistant.', 'note' => 'Tell me what you need — in your own words, in any language — or pick one below.'],
            'thanks' => ['say' => 'Any time — glad I could help.', 'note' => 'Anything else on the bathroom you want to think through?'],
            'who' => ['say' => 'LUFLY is a sanitary-ware factory: washbasins, toilets, showers, baths, mixers and the accessories that go with them — bathroom and kitchen, home and project.', 'note' => 'Tell me the room and I will point you at the right pieces.'],
            'where' => ['say' => 'We are in Türkiye and we ship worldwide — the team answers on info@lufly.tr and on WhatsApp +90 850 304 08 17.', 'note' => 'Write your city and I will get the team to confirm the delivery to you.'],
            'shipping' => ['say' => 'Yes — we ship internationally, and the team confirms the timing and the cost for your city before anything is agreed.', 'note' => 'Leave the address in the box list and they will come back with the whole picture.'],
            'price' => ['say' => 'Every price is quoted by a person, not by me — the factory works with project prices, so it depends on the piece and the quantity.', 'note' => 'Collect what you are interested in and send the list once; the team answers with the prices.'],
            'offfield' => ['say' => 'That one is outside my field, I am afraid — I only know bathrooms, kitchens and what this factory makes.', 'note' => 'But if it is about your bathroom, ask away.'],
            'talk' => ['say' => 'Of course — let us talk it through.', 'note' => 'Tell me what you are looking for, in your own words, and I will narrow it down with you.'],
        ],
        'tr' => [
            'language' => ['say' => 'Elbette — Arapça yazın, Arapça cevap veririm (أهلاً بيك 👋). Türkçe ve Çekçe de anlıyorum.', 'note' => 'Ölçü, montaj, malzeme, bulamadığınız parça — ne isterseniz sorun.'],
            'greet' => ['say' => 'Merhaba! Ben LUFLY asistanı.', 'note' => 'Ne aradığınızı kendi kelimelerinizle yazın — ya da aşağıdan seçin.'],
            'thanks' => ['say' => 'Ne demek, her zaman.', 'note' => 'Banyo için düşündüğünüz başka bir şey var mı?'],
            'who' => ['say' => 'LUFLY bir sıhhi tesisat fabrikasıdır: lavabo, klozet, duş, küvet, batarya ve aksesuarları — banyo ve mutfak, ev ve proje.', 'note' => 'Odayı söyleyin, doğru parçaları göstereyim.'],
            'where' => ['say' => 'Türkiye\'deyiz ve dünyaya gönderiyoruz — ekip info@lufly.tr ve WhatsApp +90 850 304 08 17 üzerinden cevap veriyor.', 'note' => 'Şehrinizi yazın, teslimatı ekip teyit etsin.'],
            'shipping' => ['say' => 'Evet — yurt dışına gönderiyoruz; süre ve maliyeti ekip şehrinize göre teyit eder.', 'note' => 'Adresi kutu listesine bırakın, hepsiyle birlikte dönerler.'],
            'price' => ['say' => 'Fiyatı ben değil, bir insan verir — fabrika proje fiyatıyla çalışır, parçaya ve adete göre değişir.', 'note' => 'İlgilendiğiniz parçaları toplayıp listeyi bir kez gönderin; ekip fiyatlarla döner.'],
            'offfield' => ['say' => 'O benim alanımın dışında — ben banyo, mutfak ve bu fabrikanın ürettiklerini bilirim.', 'note' => 'Ama banyonuzla ilgili bir şeyse, buyurun.'],
            'talk' => ['say' => 'Tabii — konuşalım.', 'note' => 'Ne aradığınızı kendi kelimelerinizle yazın, birlikte daraltalım.'],
        ],
        'cs' => [
            'language' => ['say' => 'Jistě — napište arabsky a odpovím arabsky (أهلاً بيك 👋). Rozumím i turecky a česky.', 'note' => 'Ptejte se na rozměry, montáž, materiál nebo na kus, který nemůžete najít.'],
            'greet' => ['say' => 'Dobrý den! Jsem asistent LUFLY.', 'note' => 'Napište, co hledáte, vlastními slovy — nebo si vyberte níže.'],
            'thanks' => ['say' => 'Není zač, rádo se stalo.', 'note' => 'Chcete ještě něco probrat ohledně koupelny?'],
            'who' => ['say' => 'LUFLY je továrna na sanitární keramiku: umyvadla, záchody, sprchy, vany, baterie a doplňky — koupelna i kuchyně, domov i projekt.', 'note' => 'Řekněte mi místnost a nasměruji vás na správné kusy.'],
            'where' => ['say' => 'Jsme v Turecku a vyvážíme do celého světa — tým odpovídá na info@lufly.tr a na WhatsAppu +90 850 304 08 17.', 'note' => 'Napište město a tým potvrdí doručení k vám.'],
            'shipping' => ['say' => 'Ano — posíláme do zahraničí; termín a cenu pro vaše město potvrdí tým předem.', 'note' => 'Nechte adresu v seznamu a vrátí se s celým obrázkem.'],
            'price' => ['say' => 'Cenu dává člověk, ne já — továrna pracuje s projektovými cenami, záleží na kusu a množství.', 'note' => 'Nasbírejte kusy, které vás zajímají, a pošlete seznam najednou; tým odpoví s cenami.'],
            'offfield' => ['say' => 'To je mimo můj obor — znám koupelny, kuchyně a to, co tahle továrna vyrábí.', 'note' => 'Ale pokud jde o vaši koupelnu, ptejte se.'],
            'talk' => ['say' => 'Jistě — popovídejme si.', 'note' => 'Napište, co hledáte, vlastními slovy, a společně to zúžíme.'],
        ],
        'ar' => [
            'language' => ['say' => 'أكيد — اكتب عربي وأنا أرد عليك بالعربي 👋 وكذلك بفهم تركي وتشيكي.', 'note' => 'اسألني عن أي حاجة: المقاسات، التركيب، الخامات — أو عن قطعة مش لاقيها.'],
            'greet' => ['say' => 'أهلاً بيك! أنا مساعد LUFLY.', 'note' => 'قولّي محتاج إيه بكلامك — أو اختار من اللي تحت.'],
            'thanks' => ['say' => 'العفو، أنا في الخدمة.', 'note' => 'في حاجة تانية في الحمام تحب نراجعها؟'],
            'who' => ['say' => 'LUFLY مصنع أدوات صحية: أحواض، قواعد حمام، دش، بانيوهات، خلاطات وكل الإكسسوارات — للحمام والمطبخ، للبيت وللمشاريع.', 'note' => 'قولّي المكان وأنا أرشّحلك الأنسب.'],
            'where' => ['say' => 'إحنا في تركيا وبنشحن لكل العالم — والفريق بيرد على info@lufly.tr وعلى واتساب +90 850 304 08 17.', 'note' => 'اكتب مدينتك والفريق يأكدلك التوصيل.'],
            'shipping' => ['say' => 'أيوه بنشحن برّه، والفريق يأكدلك الميعاد والتكلفة لمدينتك قبل أي اتفاق.', 'note' => 'سيب العنوان في قايمة البوكس والفريق يرجعلك بالصورة كاملة.'],
            'price' => ['say' => 'السعر بيحدده شخص من الفريق مش أنا — المصنع بيشتغل بأسعار المشاريع، وبتفرق حسب القطعة والكمية.', 'note' => 'اجمع اللي يعجبك وابعت القايمة مرة واحدة، والفريق يرد عليك بالأسعار.'],
            'offfield' => ['say' => 'دي برّه مجالي للأسف — أنا فاهم في الحمامات والمطابخ وفي اللي المصنع بيعمله.', 'note' => 'بس لو عن حمامك، اتفضل اسأل.'],
            'talk' => ['say' => 'طبعًا، خلينا نتكلم.', 'note' => 'قولّي بتدور على إيه بكلامك، وأنا أضيّق معاك الخيارات.'],
        ],
    ];

    /** The pieces the chat offers, in the visitor's own words. */
    private const CHIP_LABELS = [
        'ar' => [
            'basin' => 'حوض', 'toilet' => 'قاعدة حمام', 'shower' => 'دش', 'tap' => 'خلاطات',
            'access' => 'احتياجات خاصة', 'kids' => 'للأطفال', 'bath' => 'بانيو',
        ],
    ];

    /**
     * Which language the visitor is writing in — from the letters themselves, so
     * nobody has to switch the shop's language to be understood.
     */
    public function language(string $question, string $locale): string
    {
        $text = mb_strtolower(trim($question), 'UTF-8');

        if ($text !== '' && preg_match('/[\x{0600}-\x{06FF}]/u', $text) === 1) {
            return 'ar';
        }

        if ($text !== '' && preg_match('/[\x{011F}\x{011E}\x{00FC}\x{00DC}\x{015F}\x{015E}\x{0131}\x{0130}\x{00F6}\x{00D6}\x{00E7}\x{00C7}]/u', $text) === 1) {
            return 'tr';
        }

        if ($text !== '' && preg_match('/[\x{011B}\x{0161}\x{010D}\x{0159}\x{017E}\x{00FD}\x{00E1}\x{00ED}\x{00E9}\x{00FA}\x{016F}\x{0148}\x{0165}\x{010F}]/u', $text) === 1) {
            return 'cs';
        }

        /* Nothing in the letters says which language it is — so the answer follows
           the language the shop is being read in. Asking *about* Arabic ("can you
           speak Arabic?") is not writing Arabic: the answer stays in the visitor's
           own language, and says so. */
        $locale = strtolower($locale);

        return in_array($locale, self::LANGUAGES, true) ? $locale : 'en';
    }

    /** Does the visitor's sentence have anything to do with what this shop is? */
    public function inDomain(string $question): bool
    {
        $text = $this->flatten($question);

        foreach (self::DOMAIN as $word) {
            if (str_contains($text, $word)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A line for the sentence, or null when nothing here fits (the caller then
     * falls back to the shop's own wording).
     *
     * @return array{say:string,note:string,language:string,intent:string}|null
     */
    public function reply(string $question, string $locale, bool $questionLike = false): ?array
    {
        $language = $this->language($question, $locale);
        $text = $this->flatten($question);
        $intent = $this->intent($text, $questionLike);

        if ($intent === '') {
            return null;
        }

        $line = self::LINES[$language][$intent] ?? self::LINES['en'][$intent] ?? null;

        if ($line === null) {
            return null;
        }

        return [
            'say' => $line['say'],
            'note' => $line['note'],
            'language' => $language,
            'intent' => $intent,
        ];
    }

    /**
     * The questions that are about the shop itself — the language it speaks,
     * where it is, what it makes, what things cost, how they travel. A visitor
     * who asks one of those gets an answer, even when the same sentence happens
     * to name a piece ("how much is a washbasin?" is a question about prices,
     * not a reason to show a shelf).
     *
     * @return array{say:string,note:string,language:string,intent:string}|null
     */
    public function service(string $question, string $locale): ?array
    {
        $reply = $this->reply($question, $locale, false);

        if ($reply === null) {
            return null;
        }

        return in_array($reply['intent'], ['greet', 'thanks', 'language', 'who', 'where', 'price', 'shipping'], true)
            ? $reply
            : null;
    }

    /**
     * The few pieces the chat offers, written in the visitor's language. Null when
     * the language is one the shop already speaks (the storefront's own
     * translations are better there).
     *
     * @param array<int, string> $topics
     * @return array<int, array{id:string,label:string,kind:string}>|null
     */
    public function chips(string $language, array $topics): ?array
    {
        $labels = self::CHIP_LABELS[$language] ?? null;

        if ($labels === null) {
            return null;
        }

        $chips = [];

        foreach ($topics as $topic) {
            if (!isset($labels[$topic])) {
                continue;
            }

            $chips[] = ['id' => 'topic:' . $topic, 'label' => $labels[$topic], 'kind' => 'ask'];
        }

        return $chips === [] ? null : $chips;
    }

    /** The note under a chat answer, in the visitor's language. */
    public function note(string $language, string $fallback): string
    {
        $line = self::LINES[$language]['talk'] ?? null;

        return $line !== null ? $line['note'] : $fallback;
    }

    private function intent(string $text, bool $questionLike): string
    {
        foreach (['language' => self::LANGUAGE_ASK, 'greet' => self::GREET, 'thanks' => self::THANKS,
                  'who' => self::WHO, 'where' => self::WHERE, 'price' => self::PRICE,
                  'shipping' => self::SHIPPING] as $intent => $words) {
            foreach ($words as $word) {
                if (str_contains($text, $word)) {
                    return $intent;
                }
            }
        }

        if ($questionLike && !$this->inDomain($text)) {
            return 'offfield';
        }

        return $questionLike ? 'talk' : '';
    }

    /** Lower case, Arabic letters folded, extra spaces squeezed. */
    private function flatten(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = (string) preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $text);
        $text = str_replace(['أ', 'إ', 'آ', 'ى', 'ئ', 'ؤ', 'ة'], ['ا', 'ا', 'ا', 'ي', 'ي', 'و', 'ه'], $text);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
