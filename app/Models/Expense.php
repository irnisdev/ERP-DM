<?php

namespace App\Models;

use App\Models\Concerns\HasApprover;
use App\Models\Concerns\HasBranch;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasApprover;
    use HasCreator;
    use HasBranch;
    use HasFactory;
    use HasStatus;

    protected $fillable = [
        'expense_number',
        'branch_id',
        'expense_category_id',
        'supplier_id',
        'amount',
        'expense_date',
        'financial_account_id',
        'bill_id',
        'description',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'approved_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

}