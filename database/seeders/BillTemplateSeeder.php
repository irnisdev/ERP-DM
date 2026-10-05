<?php

namespace Database\Seeders;

use App\Models\BillTemplate;
use Illuminate\Database\Seeder;

/**
 * PRD 01 §17/§28: seluruh jenis tagihan memakai tabel yang sama, template
 * hanya membedakan kategori dan frekuensi.
 */
class BillTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['code' => 'BPJS-KES', 'name' => 'BPJS Kesehatan', 'category' => 'BPJS', 'frequency' => 'MONTHLY', 'amount' => 1500000, 'due_day' => 15],
            ['code' => 'BPJS-KETENAGAKERJAAN', 'name' => 'BPJS Ketenagakerjaan', 'category' => 'BPJS', 'frequency' => 'MONTHLY', 'amount' => 1200000, 'due_day' => 15],
            ['code' => 'PAM', 'name' => 'Tagihan Air PAM', 'category' => 'PAM', 'frequency' => 'MONTHLY', 'amount' => 350000, 'due_day' => 10],
            ['code' => 'INTERNET', 'name' => 'Internet Kantor Pusat', 'category' => 'INTERNET', 'frequency' => 'MONTHLY', 'amount' => 1200000, 'due_day' => 5],
            ['code' => 'PLN', 'name' => 'Listrik PLN', 'category' => 'ELECTRICITY', 'frequency' => 'MONTHLY', 'amount' => 4500000, 'due_day' => 10],
            ['code' => 'PBB', 'name' => 'PBB Kota', 'category' => 'PBB', 'frequency' => 'YEARLY', 'amount' => 25000000, 'due_day' => 31],
            ['code' => 'SEWA', 'name' => 'Sewa Tempat', 'category' => 'RENT', 'frequency' => 'QUARTERLY', 'amount' => 30000000, 'due_day' => 1],
            ['code' => 'ASURANSI', 'name' => 'Asuransi Klinik', 'category' => 'INSURANCE', 'frequency' => 'SEMESTERLY', 'amount' => 8000000, 'due_day' => 20],
            ['code' => 'CICILAN-ALAT', 'name' => 'Cicilan Alat Treatment', 'category' => 'INSTALLMENT', 'frequency' => 'MONTHLY', 'amount' => 5000000, 'due_day' => 25],
            ['code' => 'GOOGLE-WORKSPACE', 'name' => 'Google Workspace', 'category' => 'OTHER', 'frequency' => 'MONTHLY', 'amount' => 875000, 'due_day' => 7],
        ];

        foreach ($templates as $template) {
            BillTemplate::updateOrCreate(
                ['code' => $template['code']],
                [
                    'name' => $template['name'],
                    'category' => $template['category'],
                    'frequency' => $template['frequency'],
                    'default_amount' => $template['amount'],
                    'default_due_day' => $template['due_day'],
                    'status' => 'ACTIVE',
                    'notes' => null,
                ]
            );
        }
    }
}