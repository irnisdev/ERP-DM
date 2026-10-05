<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends StoreEmployeeRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('employee')) ?? false;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'manager_id.exists' => 'Manager tidak ditemukan.',
        ]);
    }

    public function withValidator($validator): void
    {
        parent::withValidator($validator);

        $validator->after(function ($validator) {
            $employee = $this->route('employee');

            if ($employee instanceof Employee && $this->input('manager_id') === $employee->id) {
                $validator->errors()->add('manager_id', 'Employee tidak boleh menjadi manager dirinya sendiri.');
            }
        });
    }

    public function employeeData(): array
    {
        $data = $this->validated();

        // employee_code tidak diubah saat update.
        unset($data['employee_code']);

        return $data;
    }
}