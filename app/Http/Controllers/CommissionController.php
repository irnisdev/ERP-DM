<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeCommission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmployeeCommission::class);

        $user = $request->user();

        $commissions = EmployeeCommission::query()
            ->with(['employee', 'salesTransaction'])
            // Therapist hanya melihat komisinya sendiri (PRD 02 §22).
            ->when($user->employee !== null && ! $user->isSuperAdmin(), fn ($q) => $q
                ->where('employee_id', $user->employee->id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('sales.commissions', [
            'commissions' => $commissions,
            'filters' => $request->only(['status', 'employee_id']),
            'statuses' => [
                EmployeeCommission::STATUS_PENDING,
                EmployeeCommission::STATUS_INCLUDED,
                EmployeeCommission::STATUS_APPROVED,
                EmployeeCommission::STATUS_VOID,
            ],
            'employees' => Employee::orderBy('full_name')->get(),
        ]);
    }
}
