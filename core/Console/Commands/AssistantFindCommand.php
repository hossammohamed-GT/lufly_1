<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;
use Modules\Assistant\Services\CatalogFinder;

/**
 * What the chat does, from the terminal — the local half of the finder, with no
 * model and no network, which is exactly the part that has to work on any host.
 *
 *   php cli assistant:find "a small oval washbasin, chrome, 55 cm"
 *   php cli assistant:find "duş bataryası"        # any language
 *
 * It prints the words it kept, the category it recognised, and the cards the
 * visitor would have seen — so a strange answer on a live database can be
 * traced without opening the site.
 */
final class AssistantFindCommand extends Command
{
    protected string $name = 'assistant:find';

    protected string $description = 'Search the catalogue the way the chat does (no AI, no tokens).';

    public function handle(array $args, array $options): int
    {
        $question = trim(implode(' ', $args));

        if ($question === '') {
            $this->info('Usage: php cli assistant:find "what you are looking for" [--locale=cs] [--limit=6]');
            return 1;
        }

        /* option() takes strings (and hands back the default when the flag is
           absent), so every value is read, then replaced when it is empty */
        $locale = trim((string) $this->option($options, 'locale', ''));
        $locale = $locale !== '' ? $locale : (string) config('localization.default', 'en');
        $limit = (int) $this->option($options, 'limit', '0');
        $limit = $limit > 0 ? $limit : (int) config('assistant.find.limit', 6);
        $finder = $this->app->get(CatalogFinder::class);

        $terms = $finder->terms($question, $locale);
        $category = $finder->categorySlug($question);
        $cards = $finder->find($terms, $locale, $limit, $category);

        $this->line('');
        $this->line('finder: ' . $question);
        $this->line('locale: ' . $locale . ' · limit: ' . $limit);
        $this->line('words : ' . ($terms === [] ? '(none kept)' : implode(', ', $terms)));
        $this->line('group : ' . ($category === '' ? '(none recognised)' : $category));
        $this->line('');

        if ($cards === []) {
            $this->error('nothing matched — the chat would show the shop\'s most-seen pieces and offer the team.');

            foreach ($finder->featured($locale, $limit) as $card) {
                $this->line('  ' . $card['code'] . '  ' . $card['name']);
            }

            return 0;
        }

        foreach ($cards as $card) {
            $this->line(sprintf(
                '  %-14s %s%s%s',
                (string) $card['code'],
                (string) $card['name'],
                $card['size'] !== '' ? '  ·  ' . $card['size'] : '',
                isset($card['score']) ? '   [' . (int) $card['score'] . ']' : '',
            ));
        }

        $best = 0;
        foreach ($cards as $card) {
            $best = max($best, (int) ($card['score'] ?? 0));
        }

        $this->line('');
        $this->line('best score: ' . $best . ' (the chat calls the model below '
            . (int) config('assistant.find.good_score', 24) . ')');

        return 0;
    }
}
