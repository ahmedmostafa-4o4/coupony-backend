<?php

namespace App\Domain\Store\DTOs\Admin;

use Illuminate\Http\UploadedFile;

class StoreCategoryDTO
{
    public function __construct(
        public readonly array $data,
        public readonly ?string $nameAr = null,
        public readonly ?string $nameEn = null,
        public readonly ?string $slug = null,
        public readonly ?int $sortOrder = null,
        public readonly ?bool $isActive = null,
        public readonly ?UploadedFile $icon = null,
        public readonly ?UploadedFile $imageCategory = null,
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            data: $data,
            nameAr: $data['name_ar'] ?? null,
            nameEn: $data['name_en'] ?? null,
            slug: $data['slug'] ?? null,
            sortOrder: $data['sort_order'] ?? null,
            isActive: $data['is_active'] ?? null,
            icon: $data['icon'] ?? null,
            imageCategory: $data['image_category'] ?? null,
        );
    }
    
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }
    
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
}
