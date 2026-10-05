<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillTemplate extends Model
{
    use Auditable;
    use HasFactory;
    use HasStatus;

    public const FREQUENCY_MONTHLY = 'MONTHLY';

    public const FREQUENCY_QUARTERLY = 'QUARTERLY';

    public const FREQUENCY_SEMESTERLY = 'SEMESTERLY';

    public const FREQUENCY_YEARLY = 'YEARLY';

    public const FREQUENCY_ONE_TIME = 'ONE_TIME';

    protected $fillable = [
        'code',
        'name',
        'category',
        'frequency',
        'default_amount',
        'default_due_day',
        'branch_id',
        'supplier_id',
        'financial_account_id',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'default_due_day' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function isRecurring(): bool
    {
        return $this->frequency !== self::FREQUENCY_ONE_TIME;
    }
}