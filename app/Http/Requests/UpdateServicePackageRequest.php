<?php

namespace App\Http\Requests;

use App\Models\ServicePackage;
use Illuminate\Foundation\Http\FormRequest;

class UpdateServicePackageRequest extends StoreServicePackageRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('servicePackage')) ?? false;
    }
}