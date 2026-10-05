<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use Auditable;
    use HasFactory;
    use HasStatus;
    use SoftDeletes;

    /**
     * Atribut yang direkam di audit log (PRD 02 §27).
     *
     * @return array<int, string>
     */
    protected function auditAttributes(): array
    {
        return [
        'customer_code',
        'name',
        'phone',
        'email',
        'customer_type',
        'status',
        ];
    }

    protected $fillable = [
        'customer_code',
        'name',
        'gender',
        'birth_date',
        'phone',
        'email',
        'address',
        'customer_type',
        'source',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    /**
     * PRD 02 §8: warning duplicate sebelum membuat record baru
     * bila phone/email sudah terdaftar.
     */
    public function scopeFindDuplicates(Builder $query, ?string $phone, ?string $email): Builder
    {
        return $query->where(function (Builder $q) use ($phone, $email) {
            if ($phone !== null && $phone !== '') {
                $q->orWhere('phone', $phone);
            }

            if ($email !== null && $email !== '') {
                $q->orWhere('email', $email);
            }
        });
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function salesTransactions(): HasMany
    {
        return $this->hasMany(SalesTransaction::class);
    }

    public function mediaAttachments(): MorphMany
    {
        return $this->morphMany(MediaAttachment::class, 'attachable');
    }
}