<?php

namespace App\Http\Requests;

use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Employee::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $branchIds = Branch::query()->pluck('id')->all();

        return [
            'employee_code' => ['nullable', 'string', 'max:50',
                Rule::unique('employees', 'employee_code')->ignore($this->route('employee'))],
            'nik' => ['nullable', 'string', 'max:30',
                Rule::unique('employees', 'nik')->ignore($this->route('employee'))],
            'full_name' => ['required', 'string', 'max:191'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['M', 'F'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:191'],
            'address' => ['nullable', 'string'],
            'marital_status' => ['nullable', Rule::in(['SINGLE', 'MARRIED', 'DIVORCED', 'WIDOWED'])],
            'employment_status' => ['required',
                Rule::in(['ACTIVE', 'INACTIVE', 'RESIGNED', 'TERMINATED'])],
            'join_date' => ['nullable', 'date'],
            'resign_date' => ['nullable', 'date', 'after_or_equal:join_date'],

            'branch_id' => ['required', Rule::in($branchIds)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'manager_id' => ['nullable', 'exists:employees,id'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'branch_id.required' => 'Cabang wajib dipilih.',
            'branch_id.in' => 'Cabang tidak valid.',
            'employment_status.in' => 'Status pekerjaan tidak valid.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'employee_code.unique' => 'Kode employee sudah terdaftar.',
            'resign_date.after_or_equal' => 'Tanggal resign harus setelah atau sama dengan tanggal join.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $managerId = $this->input('manager_id');

            if ($managerId && $managerId === $this->route('employee')) {
                $validator->errors()->add('manager_id', 'Employee tidak boleh menjadi manager dirinya sendiri.');
            }
        });
    }

    /**
     * Data siap simpan. employee_code dibangkitkan bila kosong.
     */
    public function employeeData(): array
    {
        $data = $this->validated();

        unset($data['employee_code']);

        if (empty($this->input('employee_code'))) {
            $data['employee_code'] = $this->generateCode();
        }

        return $data;
    }

    private function generateCode(): string
    {
        $code = 'EMP-'.str_pad((string) (Employee::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);
        $suffix = 1;

        while (Employee::withTrashed()->where('employee_code', $code)->exists()) {
            $code = 'EMP-'.str_pad((string) (Employee::withTrashed()->count() + $suffix + 1), 4, '0', STR_PAD_LEFT);
            $suffix++;
        }

        return $code;
    }
}