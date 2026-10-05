<?php

namespace App\Http\Controllers;

use App\Models\ServiceCategory;

class ServiceCategoryController extends SimpleMasterController
{
    protected function model(): string
    {
        return ServiceCategory::class;
    }

    protected function viewBase(): string
    {
        return 'service-category';
    }

    protected function routeBase(): string
    {
        return 'service-categories';
    }

    protected function label(): string
    {
        return 'Kategori Service';
    }

    protected function allowsParent(): bool
    {
        return true;
    }
}
