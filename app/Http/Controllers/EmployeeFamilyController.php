<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeFamilyMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeFamilyController extends Controller
{
    public function index(Request $request, Employee $employee)
    {
        $this->authorize('manageFamily', $employee);

        return response()->json(
            $employee->familyMembers()->get()
        );
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('manageFamily', $employee);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'relationship' => ['required', Rule::in(['Spouse', 'Child', 'Father', 'Mother', 'Sibling'])],
            'nik' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['M', 'F'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'is_dependent' => ['boolean'],
        ]);

        $employee->familyMembers()->create($data);

        return back()->with('success', 'Data keluarga berhasil ditambahkan.');
    }

    public function destroy(Employee $employee, EmployeeFamilyMember $member): RedirectResponse
    {
        $this->authorize('manageFamily', $employee);

        abort_unless($member->employee_id === $employee->id, 404);

        $member->delete();

        return back()->with('success', 'Data keluarga berhasil dihapus.');
    }
}