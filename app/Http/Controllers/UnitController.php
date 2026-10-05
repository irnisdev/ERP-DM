<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;

class UnitController extends SimpleMasterController
{
    protected function model(): string
    {
        return Unit::class;
    }

    protected function viewBase(): string
    {
        return 'unit';
    }

    protected function routeBase(): string
    {
        return 'units';
    }

    protected function label(): string
    {
        return 'Satuan';
    }

    protected function extraFields(): array
    {
        return ['decimal_scale'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function masterRules(?Model $ignore = null): array
    {
        return array_merge(parent::masterRules($ignore), [
            'decimal_scale' => ['nullable', 'integer', 'min:0', 'max:6'],
        ]);
    }
}
