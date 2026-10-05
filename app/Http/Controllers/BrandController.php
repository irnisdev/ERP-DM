<?php

namespace App\Http\Controllers;

use App\Models\Brand;

class BrandController extends SimpleMasterController
{
    protected function model(): string
    {
        return Brand::class;
    }

    protected function viewBase(): string
    {
        return 'brand';
    }

    protected function routeBase(): string
    {
        return 'brands';
    }

    protected function label(): string
    {
        return 'Brand';
    }

    protected function extraFields(): array
    {
        return [];
    }
}
