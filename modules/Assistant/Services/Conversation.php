<?php

declare(strict_types=1);

namespace Modules\Assistant\Services;

final class Conversation
{
    private const GREETINGS = [
        'hello', 'hi', 'hey', 'hallo', 'hej', 'yo', 'good morning', 'good evening',
        'merhaba', 'selam', 'gunaydin', 'günaydın', 'iyi aksamlar', 'iyi akşamlar',
        'ahoj', 'dobry den', 'dobrý den', 'nazdar', 'cau', 'čau', 'zdravim', 'zdravím',
        'salam', 'salaam', 'ahlan', 'marhaba', 'صباح الخير', 'مساء الخير', 'السلام عليكم',
        'اهلا', 'أهلا', 'هلا', 'مرحبا', 'ازيك', 'إزيك', 'عامل ايه', 'عامل إيه', 'اخبارك', 'أخبارك',
        'how are you', 'how r u', 'howdy', 'nice to meet you',
    ];

    private const THANKS = [
        'thanks', 'thank you', 'thx', 'ty', 'shukran', 'tesekkurler', 'teşekkürler', 'sagol', 'sağol',
        'diky', 'díky', 'dekuji', 'děkuji', 'شكرا', 'شكرًا', 'متشكر', 'تسلم', 'جزاك الله',
    ];

    private const TALK = [
        'talk', 'chat', 'ask', 'question', 'advice', 'advise', 'recommend', 'suggest', 'help me',
        'not sure', 'no idea', 'confused', 'tell me', 'explain',
        'konusmak', 'konuşmak', 'sormak', 'tavsiye', 'oner', 'öner', 'emin degilim', 'emin değilim',
        'poradit', 'porad', 'nevim', 'zeptat', 'doporuc', 'doporuč',
        'اتكلم', 'أتكلم', 'كلمني', 'اسأل', 'أسأل', 'سؤال', 'نصيحة', 'انصحني', 'أنصحني', 'محتار',
        'مش عارف', 'معرفش', 'رايك', 'رأيك', 'ايه رايك', 'إيه رأيك', 'قولي',
    ];

    private const CHOOSING = [
        'better', 'best', 'worse', 'vs', 'versus', 'or', 'prefer', 'difference', 'instead', 'worth',
        'daha iyi', 'hangisi', 'yoksa', 'fark', 'mi ', 'mu ',
        'lepsi', 'lepší', 'nejlepsi', 'nejlepší', 'nebo', 'rozdil', 'rozdíl',
        'افضل', 'أفضل', 'احسن', 'أحسن', 'ولا', 'او', 'أو', 'الفرق', 'ايه الفرق', 'إيه الفرق',
        'انهي', 'أنهي', 'مين احسن', 'مين أفضل', 'انصحني', 'انصحنى',
    ];

    private const QUESTION_OPENERS = [
        'what', 'which', 'why', 'when', 'where', 'who', 'whom', 'whose', 'how',
        'can', 'could', 'do', 'does', 'did', 'is', 'are', 'was', 'were', 'will',
        'would', 'should', 'may', 'might', 'am', 'have', 'has',
        'ne', 'nedir', 'nasil', 'nasıl', 'neden', 'hangi', 'kac', 'kaç', 'misin', 'mısın', 'musun', 'müsün',
        'co', 'co je', 'jaky', 'jaký', 'jaka', 'jaká', 'jake', 'jaké', 'proc', 'proč', 'kde', 'kdy', 'kdo', 'kolik', 'muzu', 'můžu', 'mate', 'máte',
        'ايه', 'إيه', 'ايوه', 'هل', 'مين', 'ليه', 'ازاي', 'إزاي', 'فين', 'امتى', 'امتي', 'كام', 'كم', 'ممكن', 'بتتكلم', 'تتكلم', 'عندكم', 'بتعملوا', 'بتعملو', 'محتاج اسال',
    ];

    private const WIDE = [
        'bathroom', 'toilet room', 'renovation', 'renovate', 'building', 'new bathroom', 'my bathroom',
        'banyo', 'tuvalet', 'yenileme', 'tadilat',
        'koupelna', 'koupelny', 'koupelnu', 'rekonstrukce', 'predelavka', 'předělávka',
        'حمام', 'الحمام', 'دورة المياه', 'تجديد', 'بناء', 'بيت جديد', 'شقة جديدة', 'شقه جديده',
        'اجيب ايه', 'أجيب إيه', 'هجيب ايه', 'ايه اللي اجيبه', 'إيه اللي أجيبه',
    ];

    private const DETAILS = [
        'small', 'big', 'large', 'wide', 'narrow', 'short', 'tall', 'deep', 'low', 'high', 'slim',
        'wall', 'hung', 'floor', 'standing', 'counter', 'desk', 'built', 'free', 'concealed', 'hidden',
        'sensor', 'hands', 'free', 'automatic', 'touchless',
        'chrome', 'gold', 'brass', 'black', 'white', 'matte', 'matt', 'inox', 'steel', 'nickel', 'pvd',
        'round', 'oval', 'square', 'rectangular', 'slim',
        'accessible', 'disabled', 'handicap', 'wheelchair', 'senior', 'elderly', 'grab', 'support',
        'children', 'child', 'kids', 'baby', 'school',
        'kucuk', 'küçük', 'buyuk', 'büyük', 'genis', 'geniş', 'dar', 'duvara', 'asma', 'yere', 'krom',
        'maly', 'malý', 'velky', 'velký', 'siroky', 'široký', 'uzky', 'úzký', 'nastenne', 'nástěnné',
        'stojici', 'stojící', 'deska', 'desce', 'desku', 'chrom', 'zlaty', 'zlatý', 'bily', 'bílý',
        'صغير', 'صغيره', 'صغيرة', 'كبير', 'كبيره', 'كبيرة', 'واسع', 'واسعه', 'ضيق', 'ضيّق',
        'معلقه', 'معلقة', 'ارضي', 'أرضي', 'ارضيه', 'على الرخامه', 'على الرخامة', 'ديسك',
        'كروم', 'كرومه', 'ذهبي', 'اسود', 'أسود', 'ابيض', 'أبيض', 'مطفي', 'مطفي',
        'سنسور', 'حساس', 'اوتوماتيك', 'أوتوماتيك', 'بدون لمس',
        'كرسي متحرك', 'اعاقه', 'إعاقة', 'معاق', 'كبار السن', 'مسن', 'مقبض', 'مقابض',
        'اطفال', 'أطفال', 'طفل', 'عيال', 'ولاد', 'مدارس',
    ];

    private const TOPIC_WORDS = [
        'basin' => [
            'basin', 'basins', 'washbasin', 'washbasins', 'wash basin', 'sink', 'sinks', 'lavatory', 'hand basin', 'lavabo', 'lavabosu',
            'umyvadlo', 'umyvadla', 'umyvadlem', 'umyvadlo',
            'حوض', 'حوضي', 'أحواض', 'احواض', 'مغسلة', 'مغسله', 'حوض الحمام', 'حوض الوجه', 'بانيو صغير',
        ],
        'toilet' => [
            'toilet', 'toilets', 'wc', 'wcs', 'water closet', 'klozet', 'klozety', 'tuvalet', 'tuvaletler', 'toaleta', 'toalety',
            'zachod', 'záchod', 'misa', 'mísa', 'wca',
            'قاعدة حمام', 'قاعدة الحمام', 'مرحاض', 'تواليت', 'كوليت', 'بيت الراحه', 'بيت الراحة', 'قاعده',
        ],
        'shower' => [
            'shower', 'showers', 'shower set', 'dus', 'duş', 'duşu', 'dusakabin', 'sprcha', 'sprchy', 'sprchovy', 'sprchový',
            'sprchova', 'sprchová', 'sprchovy set',
            'دش', 'الدش', 'شاور', 'دوشه', 'دوشة', 'كابينه دش', 'كابينة دش',
        ],
        'tap' => [
            'tap', 'taps', 'mixer', 'mixers', 'faucet', 'faucets', 'battery', 'batarya', 'bataryasi', 'bataryası',
            'baterie', 'baterii', 'kohoutek', 'kohoutky',
            'خلاط', 'خلاطات', 'خلاط الحمام', 'حنفيه', 'حنفية', 'صنبور', 'صنابير',
        ],
        'access' => [
            'accessible', 'disability', 'disabled', 'handicap', 'handicapped', 'wheelchair', 'senior',
            'elderly', 'engelli', 'engelsiz', 'bezbarierove', 'bezbariérové', 'bezbarierovy', 'invalidni', 'invalidní',
            'كرسي متحرك', 'ذوي الاحتياجات', 'احتياجات خاصه', 'إعاقه', 'إعاقة', 'اعاقه', 'معاق', 'كبار السن',
            'مسنين', 'مقابض حمام',
        ],
        'kids' => [
            'kids', 'kid', 'children', 'child', 'baby', 'nursery', 'cocuk', 'çocuk', 'cocuklar', 'çocuklar',
            'detska', 'dětská', 'detske', 'dětské', 'detsky', 'dětský',
            'اطفال', 'أطفال', 'طفل', 'عيال', 'ولاد', 'صغار', 'مدرسه', 'مدرسة', 'حضانه', 'حضانة',
        ],
        'bath' => [
            'bathtub', 'bathtubs', 'bath tub', 'bath', 'baths', 'kuvet', 'kuveti', 'küvet', 'kupelna vana', 'koupelnová vana', 'vana', 'vany',
            'banyo', 'banyosu', 'banyo kuveti',
            'بانيو', 'البانيو', 'حوض استحمام', 'حوض الاستحمام', 'بانيو كبير', 'بانيو صغير',
        ],
    ];

    public function intent(string $question): string
    {
        $text = $this->normalize($question);

        if ($text === '') {
            return '';
        }

        if ($this->holds($text, self::GREETINGS) && $this->words($text) <= 4) {
            return 'greet';
        }

        if ($this->holds($text, self::THANKS) && $this->words($text) <= 4) {
            return 'thanks';
        }

        if ($this->holds($text, self::CHOOSING) && ($this->holds($text, self::TALK) || $this->hasQuestionMark($question) || $this->words($text) <= 8)) {
            return 'choosing';
        }

        if ($this->holds($text, self::TALK)) {
            return 'talk';
        }

        if ($this->holds($text, self::WIDE)) {
            return 'wide';
        }

        return '';
    }

    public function asks(string $question): bool
    {
        $text = $this->normalize($question);

        if ($text === '') {
            return false;
        }

        if ($this->hasQuestionMark($question)) {
            return true;
        }

        foreach (self::QUESTION_OPENERS as $opener) {
            if (str_starts_with($text, $opener . ' ')) {
                return true;
            }
        }

        return false;
    }

    public function topic(string $question): string
    {
        $text = $this->normalize($question);

        if ($text === '') {
            return '';
        }

        $best = '';
        $score = 0;

        foreach (self::TOPIC_WORDS as $topic => $words) {
            if (!isset($this->topics()[$topic])) {
                continue;
            }

            $hit = 0;

            foreach ($words as $word) {
                if ($this->holds($text, [$word])) {
                    $hit += 1 + (int) (mb_strlen($word) / 8);
                }
            }

            if ($hit > $score) {
                $score = $hit;
                $best = $topic;
            }
        }

        return $best;
    }

    public function specific(string $question): bool
    {
        $text = $this->normalize($question);

        if ($text === '') {
            return false;
        }

        if (preg_match('/\d/', $text) === 1) {
            return true;
        }

        return $this->holds($text, self::DETAILS);
    }

    public function topicConfig(string $topic): array
    {
        $topics = $this->topics();

        return isset($topics[$topic]) && is_array($topics[$topic]) ? $topics[$topic] : [];
    }

    public function topicTerms(string $topic): array
    {
        $config = $this->topicConfig($topic);

        return array_values(array_filter(array_map('strval', (array) ($config['terms'] ?? []))));
    }

    public function topicCategory(string $topic): string
    {
        $config = $this->topicConfig($topic);

        return (string) ($config['category'] ?? '');
    }

    public function options(string $topic): array
    {
        $options = (array) config('assistant.guide.options.' . $topic, []);

        return array_values(array_filter(array_map('strval', array_keys($options))));
    }

    public function optionTerms(string $topic, string $option): array
    {
        $terms = (array) config('assistant.guide.options.' . $topic . '.' . $option . '.terms', []);

        return array_values(array_filter(array_map('strval', $terms)));
    }

    public function topicList(): array
    {
        return array_values(array_filter(array_map('strval', array_keys($this->topics())), static function (string $topic): bool {
            return !in_array($topic, ['start'], true);
        }));
    }

    public function label(string $key): string
    {
        $translated = trans('assistant.' . $key);
        $full = 'assistant.' . $key;

        return $translated === $full ? str_replace('_', ' ', $key) : $translated;
    }

    private function topics(): array
    {
        $topics = (array) config('assistant.guide.topics', []);

        return $topics;
    }

    public function words(string $text): int
    {
        return count(array_filter(explode(' ', $text), static fn (string $word): bool => $word !== ''));
    }

    private function hasQuestionMark(string $question): bool
    {
        return str_contains($question, '?') || str_contains($question, '؟');
    }

    private function holds(string $text, array $words): bool
    {
        foreach ($words as $word) {
            $needle = $this->normalize($word);

            if ($needle === '') {
                continue;
            }

            if (str_contains($needle, ' ')) {
                if (str_contains($text, $needle)) {
                    return true;
                }

                continue;
            }

            if (preg_match('/(?<![\p{L}\p{N}])' . preg_quote($needle, '/') . '(?![\p{L}\p{N}])/u', $text) === 1) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = (string) preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $text);
        $text = str_replace(['أ', 'إ', 'آ', 'ى', 'ئ', 'ؤ', 'ة'], ['ا', 'ا', 'ا', 'ي', 'ي', 'و', 'ه'], $text);
        $text = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);
        $text = (string) preg_replace('/(^| )ال(?=\p{L}{3})/u', '$1', $text);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
