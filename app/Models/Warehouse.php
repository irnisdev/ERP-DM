<?php

namespace App\Models;

use App\Models\Concerns\HasBranch;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasBranch;
    use HasFactory;
    use HasStatus;

    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'warehouse_type',
        'address',
        'status',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function opnames(): HasMany
    {
        return $this->hasMany(StockOpname::class);
    }
}