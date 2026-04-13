<?php

namespace App\Domain\Product\Models;

use App\Domain\Product\Enums\ProductRevisionAction;
use App\Domain\Product\Enums\ProductRevisionStatus;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductRevision extends Model
{
    use HasFactory;

    protected $table = 'product_revisions';

    protected $fillable = [
        'product_id',
        'revision_no',
        'action',
        'status',
        'base_revision_no',
        'submitted_by',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'admin_notes',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'revision_no' => 'integer',
            'action' => ProductRevisionAction::class,
            'status' => ProductRevisionStatus::class,
            'base_revision_no' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'payload' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
