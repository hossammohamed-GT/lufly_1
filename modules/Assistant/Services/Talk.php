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

    /**
     * The visitor wants your opinion, not a shelf: "is a bath practical?",
     * "what do you think of central vacuum?", "its pros and cons?".
     *
     * This is the question the shop is here for — it is wired to a model so it can
     * answer about things that are not on the site at all.
     */
    private const ADVICE = [
        'what do you think', 'your opinion', 'your view', 'do you recommend', 'would you recommend',
        'is it worth', 'worth it', 'worth buying', 'is it practical', 'practical', 'pros and cons',
        'advantages', 'disadvantages', 'downsides', 'drawback', 'any good', 'good idea', 'should i',
        'which is better', 'is it better', 'difference between',
        'ne dersin', 'ne düşünüyorsun', 'ne dusunuyorsun', 'tavsiye eder misin', 'onerir misin', 'tavsiye',
        'kullanışlı mı', 'kullanisli mi', 'avantaj', 'dezavantaj', 'artıları', 'eksileri', 'iyi mi', 'mantıklı mı',
        'hangisi daha iyi', 'farkı ne', 'sence', 'sizce', 'pratik mi', 'pratik', 'değer mi', 'deger mi',
        'alınır mı', 'alinir mi', 'faydalı', 'faydali', 'memnun musun',
        'co si myslite', 'co říkáte', 'doporučujete', 'doporucujete', 'vyplatí se', 'vyplati se', 'praktické',
        'prakticky', 'výhody', 'vyhody', 'nevýhody', 'nevyhody', 'má to smysl', 'ma to smysl', 'je lepší',
        'je lepsi', 'jaký je rozdíl', 'jaky je rozdil',
        'ايه رايك', 'إيه رأيك', 'رأيك', 'رايك', 'رأيكم', 'ايه رأيك', 'هل هو عملي', 'عملي', 'عمليه', 'عملى',
        'عيوب', 'عيوبه', 'عيوبها', 'مميزات', 'ميزات', 'ايجابيات', 'إيجابيات', 'سلبيات', 'مساوئ', 'اضرار',
        'ينفع', 'تنفع', 'تستاهل', 'يستاهل', 'تنصحني', 'نصيحتك', 'نصيحة', 'بتنصح', 'افضل', 'أفضل', 'احسن',
        'الفرق بين', 'ايه الفرق', 'إيه الفرق', 'محتار',
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

    /** Is he asking what we think — an opinion, a comparison, the pros and cons? */
    public function asksAdvice(string $question): bool
    {
        $text = $this->flatten($question);

        if ($text === '') {
            return false;
        }

        foreach (self::ADVICE as $word) {
            if (str_contains($text, $word)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The shop's own answer to "what do you think of it?" — the model's fallback,
     * and what a visitor reads when there is no model to ask. Null when the piece
     * is not one the shop has words about (the model then takes it, or the generic
     * line does).
     *
     * @return array{say:string,note:string,language:string,intent:string}|null
     */
    public function consult(string $question, string $locale, string $topic = ''): ?array
    {
        $language = $this->language($question, $locale);
        $say = $topic !== '' ? (self::CONSULT[$language][$topic] ?? self::CONSULT['en'][$topic] ?? '') : '';
        $note = self::LINES[$language]['talk']['note'] ?? self::LINES['en']['talk']['note'];

        if ($say === '') {
            /* nothing of our own to say about it — the model is the consultant,
               and this line is what the visitor gets if there is no model either */
            $say = self::CONSULT_GENERIC[$language] ?? self::CONSULT_GENERIC['en'];

            return [
                'say' => $say,
                'note' => $note,
                'language' => $language,
                'intent' => 'advice',
                'generic' => true,
            ];
        }

        return ['say' => $say, 'note' => $note, 'language' => $language, 'intent' => 'advice'];
    }

    /** The advice note in the visitor's language (what to do with the answer). */
    public function adviceNote(string $language, string $fallback): string
    {
        return self::LINES[$language]['talk']['note'] ?? $fallback;
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
     * The visitor's own language consultative line for one of the shop's pieces —
     * what it is good for, and the one thing worth knowing before deciding. It is
     * the fallback for the model, and the thing a visitor reads when the shop has
     * no key left or no network.
     *
     * @var array<string, array<string, string>>
     */
    private const CONSULT = [
        'en' => [
            'basin' => 'A washbasin is the piece you touch every day, so the room decides more than the design: 45–55 cm suits a guest WC or a narrow wall, 60 cm and up is what a family bathroom uses comfortably. The one thing to watch is depth — a deep bowl looks generous and then eats the room in front of it. Tell me the wall you have and I will bring the sizes that fit it.',
            'toilet' => 'Both are equally good to use, so the wall decides. A wall-hung one keeps the floor clear and makes cleaning easy — the trade-off is that the wall has to carry a concealed frame, which is more work if the wall is already finished and tiled. Floor-standing is the simpler, safer choice when the plumbing is old or the wall is not ready. If you are redoing the wall anyway, hung is the one people never regret.',
            'shower' => 'A set with a mixer is the quickest to install and the easiest to service; a concealed one looks cleaner and gives you room at shoulder height — but it goes in before the tiles, not after. So: wall still open, concealed; wall finished, a set. Either way the water supply is the same, so this is a decision about looks and time, not about performance.',
            'tap' => 'Using the wrong mixer is the most common mistake in a bathroom: a basin mixer is tall with a short reach, a kitchen one is lower with a longer spout. Get that right and it lasts a decade without a thought. Where many hands use the same tap — a clinic, a shared WC, a workshop — the sensor mixer pays for itself in water and in hygiene.',
            'access' => 'An accessible bathroom is mostly about heights and support, not about special-looking pieces: a wall-hung basin leaves the room a wheelchair needs, grab bars carry the weight instead of the ceramics, and a raised seat is simply easier to use. It is also a bathroom that is comfortable for every age — which is why it is worth doing properly the first time.',
            'kids' => 'Everything comes down to the child\'s height: a basin around 60 cm, a seat that clips onto the toilet and a step that stays put. These are ordinary pieces mounted lower, so they are easy to change when the children grow — nothing is wasted. It also means fewer wet floors and less climbing on the ceramics, which is the part parents notice most.',
            'bath' => 'A bath is the one place children and older people are safest, and nothing replaces a long soak. What it costs you is space and water, and it is more to clean than a shower — so if the room is tight, a shower enclosure gives you more to move in. If there is room for both, keep the bath and put a proper mixer on the rim; that is what people end up doing.',
        ],
        'tr' => [
            'basin' => 'Lavabo her gün dokunduğunuz parça, bu yüzden tasarımdan çok oda karar verir: 45–55 cm misafir tuvaletine ya da dar duvara, 60 cm ve üstü ailenin her gün kullandığı banyoya uyar. Dikkat edilecek tek şey derinlik — derin bir tekne cömert görünür, sonra önündeki yeri yer. Duvarınızı söyleyin, uyan ölçüleri getireyim.',
            'toilet' => 'Kullanım olarak ikisi de aynı iyidir, kararı duvar verir. Duvara monte olan zemini boş bırakır ve temizliği kolaylaştırır — bedeli, duvarın gizli bir taşıyıcı çerçeve taşımasıdır; duvar bitmiş ve fayanslıysa bu daha zahmetlidir. Tesisat eskiyse ya da duvar hazır değilse yere oturan daha basit ve güvenli seçimdir. Duvarı zaten yeniliyorsanız, monte olanı kimse pişman olmaz.',
            'shower' => 'Bataryalı set en çabuk kurulan ve servisi en kolay olan; gizli olan daha temiz durur ve omuz hizasında yer kazandırır — ama fayansdan önce girer, sonra değil. Yani duvar açıkken gizli, bittiğinde set. Su tesisatı ikisinde aynıdır; bu görünüm ve zaman kararıdır, performans değil.',
            'tap' => 'Banyoda en sık yapılan hata yanlış batarya seçmek: lavabo bataryası yüksek ve kısa erimli, mutfak bataryası daha alçak ve uzun ağızlıdır. Bunu doğru yaparsanız on yıl sorunsuz gider. Aynı musluğu çok elin kullandığı yerlerde — klinik, ortak tuvalet, atölye — sensörlü batarya su ve hijyen olarak kendini öder.',
            'access' => 'Engelsiz banyo çoğunlukla yükseklikler ve destekle ilgilidir, özel görünen parçalarla değil: duvara monte lavabo tekerlekli sandalyenin ihtiyacı olan yeri bırakır, tutamaçlar yükü seramiğe değil kendine alır, yükseltilmiş klozet kapağı kullanımı kolaylaştırır. Aynı zamanda her yaş için rahat bir banyodur — ilk seferde doğru yapmaya değer.',
            'kids' => 'Her şey çocuğun boyuna iner: lavabo yaklaşık 60 cm, klozeta takılan bir klozet adaptörü ve kaymayan bir basamak. Bunlar alçak monte edilmiş sıradan parçalar, çocuklar büyüyünce değiştirmek kolay — hiçbiri boşa gitmez. Islak zemin ve seramiğe tırmanma da azalır; anne babaların en çok fark ettiği kısım budur.',
            'bath' => 'Küvet, çocukların ve yaşlıların en güvende olduğu yerdir ve uzun bir banyo keyfini hiçbir şey değiştirmez. Bedeli yer ve su, ayrıca duştan daha çok temizlik ister — oda darsa duşakabin hareket için daha fazla yer verir. İkisine de yer varsa küveti tutun ve kenarına doğru bir batarya koyun; insanların çoğu bunu yapıyor.',
        ],
        'cs' => [
            'basin' => 'Umyvadlo je kus, kterého se dotýkáte každý den, takže rozhoduje místnost víc než design: 45–55 cm se hodí do WC pro hosty nebo na úzkou stěnu, 60 cm a více je to, co rodinná koupelna používá denně. Jediné, na co si dát pozor, je hloubka — hluboká mísa vypadá velkoryse a pak sebere prostor před sebou. Řekněte mi stěnu a přinesu míry, které sedí.',
            'toilet' => 'Používání je u obou stejně dobré, rozhoduje stěna. Závěsná nechá volnou podlahu a snadno se uklízí — daň je, že stěna musí nést skrytý nosný rám, což je víc práce, když je stěna už hotová a obložená. Stojící je jednodušší a bezpečnější volba, když je instalace stará nebo stěna není připravená. Pokud stěnu stejně předěláváte, závěsné nikdo nelituje.',
            'shower' => 'Sestava s baterií se instaluje nejrychleji a nejsnáze se servisuje; skrytá vypadá čistěji a dá vám prostor v úrovni ramen — ale jde do zdi před obkladem, ne po něm. Takže: stěna otevřená, skrytá; stěna hotová, sestava. Rozvod vody je u obou stejný — tohle je rozhodnutí o vzhledu a čase, ne o výkonu.',
            'tap' => 'Špatná baterie je v koupelně nejčastější chyba: umyvadlová je vyšší s krátkým dosahem, dřezová nižší s delším výtokem. Když to sedí, vydrží deset let bez myšlenky. Tam, kde se jednoho kohoutku dotýká mnoho rukou — ordinace, společné WC, dílna — se senzorová baterie zaplatí vodou i hygienou.',
            'access' => 'Bezbariérová koupelna je hlavně o výškách a podpoře, ne o zvláštně vypadajících kusech: závěsné umyvadlo nechá prostor pro vozík, madla nesou váhu místo keramiky a zvýšené sedátko se prostě snáz používá. Zároveň je to koupelna pohodlná pro každý věk — proto se vyplatí udělat ji hned správně.',
            'kids' => 'Všechno jde do výšky dítěte: umyvadlo kolem 60 cm, sedátko, které se nacvakne na záchod, a stupínek, který drží. Jsou to běžné kusy namontované níž, takže se dají snadno vyměnit, až děti vyrostou — nic nepřijde nazmar. Ubude i mokré podlahy a šplhání po keramice, čehož si rodiče všimnou nejvíc.',
            'bath' => 'Vana je místo, kde jsou děti a starší lidé v největším bezpečí, a dlouhé máčení nic nenahradí. Stojí vás ale prostor a vodu a dá víc práce s úklidem než sprcha — když je místnost těsná, sprchový kout dá víc pohybu. Když je místo na obojí, vanu si nechte a na okraj dejte pořádnou baterii; tak to lidé nakonec dělají.',
        ],
        'ar' => [
            'basin' => 'الحوض هو القطعة اللي بتلمسها كل يوم، فالمقاس أهم من الشكل: ٤٥–٥٥ سم مناسب لدورة مياه الضيوف أو حيطة ضيقة، و٦٠ سم وأكثر هو اللي البيت بيستخدمه كل يوم براحة. الحاجة الوحيدة اللي تاخد بالك منها هي العمق: الحوض العميق شكله كريم بس بياخد مساحة قدامه. قوللي الحيطة عندك كام وأنا أجيبلك المقاسات اللي تناسبها.',
            'toilet' => 'الاستخدام في الاتنين زي بعض، واللي بيقرر هو الحيطة. المعلّق بيسيب الأرض فاضية وينضّف بسهولة — بس الحيطة لازم تشيل شاسيه مخفي، وده تعب أكتر لو الحيطة متشطبة وخلاص. القاعدة الثابتة أسهل وأأمن لو المواسير قديمة أو الحيطة مش جاهزة. ولو هتتجدد الحيطة أصلاً، المعلّق ده اللي محدش بيتحسّر عليه.',
            'shower' => 'طقم الدش بالخلاط أسرع في التركيب وأسهل في الصيانة؛ والمخفي شكله أنضف وبيديك مساحة عند مستوى الكتف — بس بيتركّب قبل السيراميك مش بعده. يعني الحيطة لسه مفتوحة: مخفي، والحيطة خلصت: طقم. مصدر المياه واحد في الحالتين، فالموضوع شكل ووقت مش أداء.',
            'tap' => 'أشهر غلطة في الحمام هي الخلاط الغلط: خلاط الحوض طويل ومداه قصير، وخلاط المطبخ أقصر بماسورة أطول. لو ظبطت دي، تعيش معاك عشر سنين من غير ما تفكر فيها. وفي الأماكن اللي إيدين كتير بتستخدم نفس الحنفية — عيادة، دورة مشتركة، ورشة — الخلاط بالحساس بيوفّر مياه ونظافة ويرجّع تمنه.',
            'access' => 'الحمام المخصص لذوي الاحتياجات أهم حاجة فيه الارتفاعات والسند مش الشكل: حوض معلّق بيسيب المساحة اللي الكرسي المتحرك محتاجها، والمقابض هي اللي بتشيل الوزن مش السيراميك، والقاعدة المرتفعة أسهل في الاستخدام. وهو نفس الوقت حمام مريح لكل الأعمار — وعشان كده بيستاهل يتعمل صح من أول مرة.',
            'kids' => 'كل حاجة بتنزل لمستوى الطفل: حوض حوالي ٦٠ سم، وقاعدة بتتركّب على القاعدة الكبيرة، وسلّم ثابت مايزحلقش. دي قطع عادية متركبة أوطى، وسهل تغييرها لما الأطفال يكبّروا — يعني مفيش حاجة بتترمي. كمان الأرض بتفضل أنشف مافيهاش طلوع على السيراميك، وده أكتر حاجة الأم والأب بيحسوها.',
            'bath' => 'البانيو هو أأمن مكان للأطفال وكبار السن، ومفيش حاجة تعوّض الجلسة الطويلة في المية الدافية. بس تمنه مساحة ومياه، وتنضيفه أكتر من الدش — فلو المكان ضيّق، كابينة الدش بتديك حرية أكبر. ولو فيه مساحة للاتنين، خلّي البانيو وحطّ عليه خلاط نضيف، وده اللي الناس بتعمله في الآخر.',
        ],
    ];

    /** The generic consult line, for a piece the shop has no words of its own about. */
    private const CONSULT_GENERIC = [
        'en' => 'Tell me the piece and I will give you my honest read: what it is good for, what to watch out for, and what suits a room like yours. If it is something we do not make, I will say so and point you at what we do.',
        'tr' => 'Parçayı söyleyin, size dürüst kanaatimi vereyim: neye iyi gelir, nelere dikkat edilir ve sizin gibi bir odaya ne uyar. Bizim üretmediğimiz bir şeyse açıkça söyler, neyi ürettiğimize yönlendiririm.',
        'cs' => 'Řekněte mi ten kus a dám vám svůj poctivý názor: k čemu je dobrý, na co si dát pozor a co se hodí do místnosti, jako je ta vaše. Pokud to nevyrábíme, řeknu to a nasměruju vás na to, co děláme.',
        'ar' => 'قوللي القطعة وأنا أقولك رأيي بصراحة: بتنفع في إيه، وإيه اللي تاخد بالك منه، وإيه الأنسب لمكانك. ولو حاجة مش بنعملها هقولك على طول وأوريك اللي بنعمله.',
    ];

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
