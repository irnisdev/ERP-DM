<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeInsurance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeInsuranceController extends Controller
{
    /**
     * @return array<string, string>
     */
    private const TYPES = [
        'BPJS_KES' => 'BPJS Kesehatan',
        'BPJS_KETENAGAKERJAAN' => 'BPJS Ketenagakerjaan',
        'ASURANSI' => 'Asuransi',
        'OTHER' => 'Lainnya',
    ];

    public function index(Request $request, Employee $employee)
    {
        $this->authorize('manageInsurance', $employee);

        return response()->json($employee->insurances()->get());
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('manageInsurance', $employee);

        $data = $request->validate([
            'insurance_type' => ['required', Rule::in(array_keys(self::TYPES))],
            'provider' => ['nullable', 'string', 'max:100'],
            'membership_number' => ['required', 'string', 'max:100'],
            'insurance_class' => ['nullable', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'employee_contribution' => ['nullable', 'numeric', 'min:0'],
            'company_contribution' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['employee_contribution'] = $data['employee_contribution'] ?? 0;
        $data['company_contribution'] = $data['company_contribution'] ?? 0;

        $employee->insurances()->create($data);

        return back()->with('success', 'Data BPJS berhasil ditambahkan.');
    }

    public function destroy(Employee $employee, EmployeeInsurance $insurance): RedirectResponse
    {
        $this->authorize('manageInsurance', $employee);

        abort_unless($insurance->employee_id === $employee->id, 404);

        $insurance->delete();

        return back()->with('success', 'Data BPJS berhasil dihapus.');
    }
}