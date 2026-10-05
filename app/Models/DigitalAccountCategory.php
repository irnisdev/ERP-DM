<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DigitalAccountCategory extends Model
{
    use Auditable;
    use HasFactory;
    use HasStatus;

    protected $fillable = [
        'code',
        'name',
        'status',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(DigitalAccount::class, 'category_id');
    }
}