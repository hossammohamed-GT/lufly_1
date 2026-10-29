<?php

declare(strict_types=1);

namespace Modules\Products\Requests;

use Core\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public int|string|null $productId = null;

    public function forProduct(int|string $id): static
    {
        $this->productId = $id;

        return $this;
    }

    public function rules(): array
    {
        $leafIds = app(\App\Services\CategoryService::class)->leafIds();
        $categoryRule = $leafIds === []
            ? 'nullable|integer'
            : 'nullable|integer|in:' . implode(',', $leafIds);

        $rules = [
            'model_code' => 'required|string|max:100|unique:products,model_code,' . $this->productId,
            'slug' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'category_id' => $categoryRule,
            'collection_id' => 'nullable|integer',
            'brand_id' => 'nullable|integer',
            'is_featured' => 'nullable|in:0,1',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:draft,active,hidden,discontinued,coming_soon',
            'image' => 'nullable|file',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'variants' => 'nullable|array',
            'specs' => 'nullable|array',
            'features' => 'nullable|array',
            'seo' => 'nullable|array',
            'dim_width_mm' => 'nullable|numeric|min:0',
            'dim_height_mm' => 'nullable|numeric|min:0',
            'dim_depth_mm' => 'nullable|numeric|min:0',
            'dim_weight_kg' => 'nullable|numeric|min:0',
            'relations_present' => 'nullable|in:0,1',
            'color_variant_ids' => 'nullable|array',
            'seat_cover_ids' => 'nullable|array',
            'wc_ids' => 'nullable|array',
            'seat_cover_only' => 'nullable|in:0,1',
            'datasheet_delete' => 'nullable|array',
        ];

        $translator = app(\Core\Localization\Translator::class);
        $locales = $translator->locales();
        $fallback = (string) config('localization.fallback', 'en');

        foreach ($locales as $locale) {
            $rules['name_' . $locale] = ($locale === $fallback ? 'required|' : 'nullable|') . 'string|max:255';
            $rules['short_description_' . $locale] = 'nullable|string|max:255';
            $rules['description_' . $locale] = 'nullable|string';
        }

        return $rules;
    }
}
