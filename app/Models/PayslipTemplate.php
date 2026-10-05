<?php

namespace App\Models;

use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayslipTemplate extends Model
{
    use HasFactory;
    use HasStatus;

    protected $fillable = [
        'code',
        'name',
        'layout_config',
        'status',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'layout_config' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class, 'template_id');
    }

    public static function default(): ?self
    {
        return static::query()->where('is_default', true)->where('status', 'ACTIVE')->first();
    }
}