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
        return [
            'model_code' => 'required|string|max:100|unique:products,model_code,' . $this->productId,
            'slug' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|integer',
            'collection_id' => 'nullable|integer',
            'brand_id' => 'nullable|integer',
            'is_featured' => 'nullable|in:0,1',
            'status' => 'required|in:draft,active,hidden,discontinued,coming_soon',
            'name_en' => 'required|string|max:255',
            'name_tr' => 'nullable|string|max:255',
            'name_cs' => 'nullable|string|max:255',
            'short_description_en' => 'nullable|string|max:500',
            'short_description_tr' => 'nullable|string|max:500',
            'short_description_cs' => 'nullable|string|max:500',
            'description_en' => 'nullable|string',
            'description_tr' => 'nullable|string',
            'description_cs' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ];
    }
}
