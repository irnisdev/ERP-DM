<?php

namespace Database\Seeders;

use App\Models\FinancialAccount;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = FinancialAccount::where('status', 'ACTIVE')
            ->whereIn('account_type', [FinancialAccount::TYPE_CASH, FinancialAccount::TYPE_BANK])
            ->pluck('id', 'account_type')
            ->all();

        $methods = [
            ['code' => 'CASH', 'name' => 'Tunai', 'method_type' => 'CASH', 'account_type' => FinancialAccount::TYPE_CASH],
            ['code' => 'TRANSFER', 'name' => 'Transfer Bank', 'method_type' => 'BANK_TRANSFER', 'account_type' => FinancialAccount::TYPE_BANK],
            ['code' => 'QRIS', 'name' => 'QRIS', 'method_type' => 'QRIS', 'account_type' => FinancialAccount::TYPE_BANK],
            ['code' => 'DEBIT_CARD', 'name' => 'Kartu Debit', 'method_type' => 'CARD', 'account_type' => FinancialAccount::TYPE_BANK],
            ['code' => 'EWALLET', 'name' => 'E-Wallet', 'method_type' => 'E_WALLET', 'account_type' => null],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                [
                    'name' => $method['name'],
                    'method_type' => $method['method_type'],
                    'financial_account_id' => $method['account_type'] !== null
                        ? ($accounts[$method['account_type']] ?? null)
                        : null,
                    'status' => 'ACTIVE',
                ]
            );
        }
    }
}