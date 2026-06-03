<?php

namespace App\Domain\Product\DTOs\Admin;

use Illuminate\Http\UploadedFile;

class CategoryDTO
{
    public function __construct(
        public readonly array $data,
        public readonly ?string $nameEn = null,
        public readonly ?string $nameAr = null,
        public readonly ?string $slug = null,
        public readonly ?string $description = null,
        public readonly ?int $parentId = null,
        public readonly ?int $sortOrder = null,
        public readonly ?bool $isActive = null,
        public readonly ?UploadedFile $icon = null,
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            data: $data,
            nameEn: $data['name_en'] ?? null,
            nameAr: $data['name_ar'] ?? null,
            slug: $data['slug'] ?? null,
            description: $data['description'] ?? null,
            parentId: $data['parent_id'] ?? null,
            sortOrder: $data['sort_order'] ?? null,
            isActive: $data['is_active'] ?? null,
            icon: $data['icon'] ?? null,
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
