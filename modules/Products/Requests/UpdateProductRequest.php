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
        $rules = [
            'model_code' => 'required|string|max:100|unique:products,model_code,' . $this->productId,
            'slug' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|integer',
            'collection_id' => 'nullable|integer',
            'brand_id' => 'nullable|integer',
            'is_featured' => 'nullable|in:0,1',
            'status' => 'required|in:draft,active,hidden,discontinued,coming_soon',
            'image' => 'nullable|file',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ];

        $translator = app(\Core\Localization\Translator::class);
        $locales = $translator->locales();
        $fallback = (string) config('localization.fallback', 'en');

        foreach ($locales as $locale) {
            $rules['name_' . $locale] = ($locale === $fallback ? 'required|' : 'nullable|') . 'string|max:255';
            $rules['short_description_' . $locale] = 'nullable|string|max:500';
            $rules['description_' . $locale] = 'nullable|string';
        }

        return $rules;
    }
}
