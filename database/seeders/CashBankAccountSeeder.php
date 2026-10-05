<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\FinancialAccount;
use Illuminate\Database\Seeder;

class CashBankAccountSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();

        $accountNumber = 1000000000;
        $index = 0;

        foreach ($branches as $branch) {
            $index++;

            FinancialAccount::updateOrCreate(
                ['account_code' => 'CASH-'.$branch->code],
                [
                    'account_type' => FinancialAccount::TYPE_CASH,
                    'bank_id' => null,
                    'branch_id' => $branch->id,
                    'account_number' => null,
                    'account_name' => 'Kas '.$branch->name,
                    'currency' => 'IDR',
                    'opening_balance' => 0,
                    'status' => 'ACTIVE',
                ]
            );

            FinancialAccount::updateOrCreate(
                ['account_code' => 'BCA-'.$branch->code],
                [
                    'account_type' => FinancialAccount::TYPE_BANK,
                    'bank_id' => \App\Models\Bank::where('code', 'BCA')->value('id'),
                    'branch_id' => $branch->id,
                    'account_number' => (string) ($accountNumber + $index),
                    'account_name' => 'PT Dian Mustika - '.$branch->name,
                    'currency' => 'IDR',
                    'opening_balance' => 0,
                    'status' => 'ACTIVE',
                ]
            );
        }

        FinancialAccount::updateOrCreate(
            ['account_code' => 'BNI-OPERASIONAL'],
            [
                'account_type' => FinancialAccount::TYPE_BANK,
                'bank_id' => \App\Models\Bank::where('code', 'BNI')->value('id'),
                'branch_id' => null,
                'account_number' => '1050000001',
                'account_name' => 'PT Dian Mustika - Operasional',
                'currency' => 'IDR',
                'opening_balance' => 0,
                'status' => 'ACTIVE',
            ]
        );
    }
}