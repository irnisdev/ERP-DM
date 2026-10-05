<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;

class ProductCategoryController extends SimpleMasterController
{
    protected function model(): string
    {
        return ProductCategory::class;
    }

    protected function viewBase(): string
    {
        return 'product-category';
    }

    protected function routeBase(): string
    {
        return 'product-categories';
    }

    protected function label(): string
    {
        return 'Kategori Product';
    }

    protected function allowsParent(): bool
    {
        return true;
    }
}
