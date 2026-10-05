<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            ['code' => 'SHIFT-1', 'name' => 'Shift Pagi', 'start_time' => '08:00:00', 'end_time' => '16:00:00', 'break_minutes' => 60],
            ['code' => 'SHIFT-2', 'name' => 'Shift Siang', 'start_time' => '12:00:00', 'end_time' => '20:00:00', 'break_minutes' => 60],
            ['code' => 'SHIFT-3', 'name' => 'Shift Malam', 'start_time' => '20:00:00', 'end_time' => '04:00:00', 'break_minutes' => 60],
            ['code' => 'SHIFT-FLEX', 'name' => 'Shift Fleksibel', 'start_time' => '10:00:00', 'end_time' => '18:00:00', 'break_minutes' => 30],
        ];

        foreach ($shifts as $shift) {
            Shift::updateOrCreate(
                ['code' => $shift['code']],
                [
                    'name' => $shift['name'],
                    'start_time' => $shift['start_time'],
                    'end_time' => $shift['end_time'],
                    'break_minutes' => $shift['break_minutes'],
                    'status' => 'ACTIVE',
                ]
            );
        }
    }
}