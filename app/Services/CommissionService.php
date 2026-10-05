<?php

namespace App\Services;

use App\Models\CommissionRule;
use App\Models\Employee;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

/**
 * CommissionService — komisi berasal dari sales item service.
 *
 * PRD 02 §23: Treatment = Rp200.000, rule 20% → komisi Rp40.000.
 *
 * Prioritas resolution rule (paling spesifik dulu):
 * 1. rule milik employee + service yang sama
 * 2. rule milik employee saja
 * 3. rule milik service saja (berlaku untuk semua employee)
 * 4. tidak ada rule → tidak ada komisi
 */
class CommissionService
{
    /**
     * Rule yang berlaku untuk employee + service pada tanggal tertentu.
     */
    public function resolveRule(?Employee $employee, ?Service $service, ?string $date = null): ?CommissionRule
    {
        $date ??= now()->toDateString();

        if ($employee === null || $service === null) {
            return null;
        }

        $scopes = [
            ['employee_id' => $employee->id, 'service_id' => $service->id],
            ['employee_id' => $employee->id, 'service_id' => null],
            ['employee_id' => null, 'service_id' => $service->id],
        ];

        foreach ($scopes as $scope) {
            $rule = CommissionRule::query()
                ->where('status', 'ACTIVE')
                ->where('employee_id', $scope['employee_id'])
                ->when(
                    $scope['service_id'] === null,
                    fn ($q) => $q->whereNull('service_id'),
                    fn ($q) => $q->where('service_id', $scope['service_id'])
                )
                ->whereDate('effective_date', '<=', $date)
                ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date))
                ->orderByDesc('effective_date')
                ->first();

            if ($rule !== null) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * Menghitung nilai komisi tanpa menyimpan apa pun.
     *
     * Basis komisi adalah nilai bersih item: (harga × qty) − diskon.
     */
    public function calculate(?Employee $employee, Service $service, float $baseAmount, ?string $date = null): float
    {
        $rule = $this->resolveRule($employee, $service, $date);

        if ($rule === null || $baseAmount <= 0) {
            return 0.0;
        }

        return $rule->calculate($baseAmount);
    }

    /**
     * Membuat record komisi untuk satu sales item.
     *
     * Idempoten terhadap (sales_transaction_item_id, employee_id) agar
     * regenerate komisi tidak menghasilkan duplikat.
     */
    public function accrue(
        \App\Models\SalesTransactionItem $item,
        \App\Models\Employee $employee
    ): ?\App\Models\EmployeeCommission {
        if ($item->item_type !== \App\Models\SalesTransactionItem::TYPE_SERVICE) {
            return null;
        }

        if ($item->service_id === null || $employee->id === null) {
            return null;
        }

        $service = $item->service;

        if ($service === null) {
            return null;
        }

        $baseAmount = ((float) $item->unit_price * (float) $item->quantity) - (float) $item->discount;
        $amount = $this->calculate($employee, $service, $baseAmount);

        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(fn () => \App\Models\EmployeeCommission::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'sales_transaction_item_id' => $item->id,
            ],
            [
                'sales_transaction_id' => $item->sales_transaction_id,
                'commission_amount' => $amount,
                'status' => \App\Models\EmployeeCommission::STATUS_PENDING,
            ]
        ));
    }
}