<?php

declare(strict_types=1);

namespace Modules\Announcements\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SEOService;
use Core\Http\RedirectResponse;
use Core\Http\Request;
use Core\Http\Response;
use Core\Localization\Translator;
use Modules\Announcements\Services\AnnouncementService;

class AnnouncementController extends Controller
{
    public function __construct(
        private readonly AnnouncementService $announcements,
        private readonly Translator $translator,
    ) {
    }

    public function index(Request $request): Response
    {
        $paginator = $this->announcements->paginate((int) $request->query('page', '1'), 15);

        $items = array_map(
            fn (object $a): array => $this->present($a),
            $paginator->items(),
        );

        return $this->view('announcements::Admin.index', [
            'title' => trans('common.announcements'),
            'paginator' => $paginator,
            'items' => $items,
        ]);
    }

    public function create(): Response
    {
        return $this->view('announcements::Admin.form', [
            'title' => trans('common.create_announcement'),
            'announcement' => null,
            'translations' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $announcement = $this->announcements->create(
            $this->coreFields($data),
            $this->translationsFromForm($data),
        );

        return $this->redirect(route('admin.announcements.index'))
            ->with('_success', trans('common.saved'));
    }

    public function edit(int $id): Response
    {
        $announcement = $this->announcements->find($id);

        return $this->view('announcements::Admin.form', [
            'title' => trans('common.edit_announcement'),
            'announcement' => $announcement,
            'translations' => $announcement->translations(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $this->validated($request);

        $this->announcements->update($id, $this->coreFields($data), $this->translationsFromForm($data));

        return $this->redirect(route('admin.announcements.index'))
            ->with('_success', trans('common.saved'));
    }

    public function toggle(int $id): RedirectResponse
    {
        $this->announcements->toggle($id);

        return $this->redirect(route('admin.announcements.index'))
            ->with('_success', trans('common.saved'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->announcements->delete($id);

        return $this->redirect(route('admin.announcements.index'))
            ->with('_success', trans('common.deleted'));
    }

    private function present(object $announcement): array
    {
        $locale = $this->translator->getLocale();

        return array_merge($announcement->translate($locale), [
            'id' => (int) $announcement->id,
            'is_active' => (int) $announcement->is_active,
            'placement' => (string) $announcement->placement,
            'style' => (string) $announcement->style,
            'link_url' => $announcement->link_url,
            'starts_at' => $announcement->starts_at,
            'ends_at' => $announcement->ends_at,
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'message_en' => 'required|string|max:500',
            'message_tr' => 'nullable|string|max:500',
            'message_cs' => 'nullable|string|max:500',
            'cta_en' => 'nullable|string|max:150',
            'cta_tr' => 'nullable|string|max:150',
            'cta_cs' => 'nullable|string|max:150',
            'placement' => 'required|in:topbar,home_banner',
            'style' => 'required|in:promo,info,warning',
            'link_url' => 'nullable|string|max:255',
            'is_active' => 'nullable|in:0,1',
            'starts_at' => 'nullable|string|max:19',
            'ends_at' => 'nullable|string|max:19',
        ]);
    }

    private function coreFields(array $data): array
    {
        return [
            'placement' => (string) ($data['placement'] ?? 'topbar'),
            'style' => (string) ($data['style'] ?? 'promo'),
            'link_url' => ($data['link_url'] ?? '') !== '' ? $data['link_url'] : null,
            'is_active' => (int) (($data['is_active'] ?? '0') === '1'),
            'starts_at' => $this->dbDateTime($data['starts_at'] ?? ''),
            'ends_at' => $this->dbDateTime($data['ends_at'] ?? ''),
        ];
    }

    private function dbDateTime(mixed $value): ?string
    {
        $value = str_replace('T', ' ', trim((string) $value));

        if ($value === '') {
            return null;
        }

        if (strlen($value) === 16) {
            $value .= ':00';
        }

        return $value;
    }

    private function translationsFromForm(array $data): array
    {
        $translations = [];

        foreach ($this->translator->locales() as $locale) {
            $message = trim((string) ($data['message_' . $locale] ?? ''));
            $cta = trim((string) ($data['cta_' . $locale] ?? ''));

            if ($message !== '') {
                $translations[$locale] = [
                    'message' => $message,
                    'cta_label' => $cta !== '' ? $cta : null,
                ];
            }
        }

        return $translations;
    }
}
