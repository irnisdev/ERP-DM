<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use Auditable;
    use HasFactory;
    use HasStatus;

    protected $fillable = [
        'code',
        'name',
        'decimal_scale',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'decimal_scale' => 'integer',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}