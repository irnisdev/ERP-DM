<?php

namespace App\Models;

use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Password/secret tidak disimpan plaintext. Kolom credential tidak ada di tabel
 * ini; bila dibutuhkan, gunakan encrypted credential storage dengan akses
 * sangat terbatas dan audit.
 */
class DigitalAccount extends Model
{
    use HasFactory;
    use HasStatus;

    protected $fillable = [
        'category_id',
        'name',
        'username',
        'email',
        'url',
        'owner_type',
        'owner_id',
        'renewal_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'renewal_date' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DigitalAccountCategory::class);
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function needsRenewal(int $withinDays = 30): bool
    {
        return $this->renewal_date !== null && $this->renewal_date->lte(now()->addDays($withinDays));
    }
}