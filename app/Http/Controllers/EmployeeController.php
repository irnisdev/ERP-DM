<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeInsurance;
use App\Models\Position;
use App\Support\BranchScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request, BranchScope $branchScope): View
    {
        $this->authorize('viewAny', Employee::class);

        $employees = Employee::query()
            ->with(['branch', 'department', 'position'])
            ->tap(fn ($q) => $branchScope->apply(request()->user(), $q))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';
                $query->where(fn ($q) => $q
                    ->where('employee_code', 'like', $search)
                    ->orWhere('full_name', 'like', $search)
                    ->orWhere('nik', 'like', $search)
                    ->orWhere('phone', 'like', $search));
            })
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('employment_status'), fn ($q) => $q->where('employment_status', $request->string('employment_status')))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        return view('master.employee.index', [
            'employees' => $employees,
            'branches' => Branch::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'filters' => $request->only(['search', 'branch_id', 'department_id', 'employment_status']),
        ]);
    }

    public function create(Request $request, BranchScope $branchScope): View
    {
        $this->authorize('create', Employee::class);

        $employee = new Employee([
            'employment_status' => 'ACTIVE',
            'status' => 'ACTIVE',
        ]);

        // User cabang otomatis diarahkan ke cabangnya sendiri.
        if ($userBranchId = $branchScope->currentBranchId($request->user())) {
            $employee->branch_id = $userBranchId;
        }

        return view('master.employee.form', [
            'employee' => $employee,
            'branches' => $this->branchOptions($request, $branchScope),
            'departments' => Department::orderBy('name')->get(),
            'positions' => Position::orderBy('name')->get(),
            'managers' => Employee::where('status', 'ACTIVE')->orderBy('full_name')->get(),
        ]);
    }

    public function store(StoreEmployeeRequest $request, BranchScope $branchScope): RedirectResponse
    {
        // Branch user tidak boleh membuat employee di luar cabangnya.
        if ($branchScope->allowed($request->user()) !== null
            && ! $branchScope->canAccess($request->user(), (int) $request->input('branch_id'))) {
            abort(403, 'Anda tidak dapat membuat employee di luar cabang Anda.');
        }

        $employee = Employee::create($request->employeeData());

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', "Employee {$employee->employee_code} berhasil dibuat.");
    }

    public function show(Request $request, Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load([
            'branch', 'department', 'position', 'manager',
            'familyMembers', 'insurances', 'shifts',
        ]);

        return view('master.employee.show', [
            'employee' => $employee,
            'canManageFamily' => $request->user()->can('manageFamily', $employee),
            'canManageInsurance' => $request->user()->can('manageInsurance', $employee),
            'canViewSalary' => $request->user()->can('viewSalary', $employee),
        ]);
    }

    public function edit(Request $request, Employee $employee, BranchScope $branchScope): View
    {
        $this->authorize('update', $employee);

        return view('master.employee.form', [
            'employee' => $employee,
            'branches' => $this->branchOptions($request, $branchScope),
            'departments' => Department::orderBy('name')->get(),
            'positions' => Position::orderBy('name')->get(),
            'managers' => Employee::where('status', 'ACTIVE')
                ->whereKeyNot($employee->id)
                ->orderBy('full_name')
                ->get(),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->employeeData());

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Data employee berhasil diperbarui.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('success', "Employee {$employee->employee_code} dinonaktifkan.");
    }

    /**
     * Super admin melihat semua cabang; user lain hanya cabangnya sendiri.
     */
    private function branchOptions(Request $request, BranchScope $branchScope)
    {
        $query = Branch::orderBy('name');

        $allowed = $branchScope->allowed($request->user());
        if ($allowed !== null) {
            $query->whereIn('id', $allowed);
        }

        return $query->get();
    }
}