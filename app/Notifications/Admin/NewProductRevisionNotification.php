<?php

namespace App\Notifications\Admin;

use App\Domain\Product\Models\ProductRevision;

class NewProductRevisionNotification extends AdminNotification
{
    public function __construct(public ProductRevision $revision) {}

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'New Product Revision',
            'message' => "A new product revision for '{$this->revision->product->name}' is pending approval.",
            'reference_type' => ProductRevision::class,
            'reference_id' => $this->revision->id,
            'data' => [
                'revision_id' => $this->revision->id,
                'product_id' => $this->revision->product_id,
            ]
        ];
    }
}
