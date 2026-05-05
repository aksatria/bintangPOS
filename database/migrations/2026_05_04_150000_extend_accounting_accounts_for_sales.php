<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $accounts = [
            ['code' => '1103', 'name' => 'Piutang Pelanggan', 'type' => 'asset'],
            ['code' => '2102', 'name' => 'PPN Keluaran', 'type' => 'liability'],
            ['code' => '4101', 'name' => 'Penjualan', 'type' => 'income'],
            ['code' => '4102', 'name' => 'Potongan Penjualan', 'type' => 'contra_income'],
        ];

        foreach ($accounts as $account) {
            DB::table('accounts')->updateOrInsert(
                ['code' => $account['code']],
                $account + ['is_active' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('accounts')->whereIn('code', ['1103', '2102', '4101', '4102'])->delete();
    }
};
