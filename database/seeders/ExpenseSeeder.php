<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->whereIn('role', ['owner', 'admin'])->get();

        Expense::query()->delete();

        if ($users->isEmpty()) {
            return;
        }

        $expenseRows = [
            ['category' => 'Utilitas', 'title' => 'Listrik Toko', 'amount' => 385000, 'note' => 'Pembayaran listrik bulanan toko.'],
            ['category' => 'Utilitas', 'title' => 'Air PDAM', 'amount' => 146000, 'note' => 'Tagihan air bulan berjalan.'],
            ['category' => 'Utilitas', 'title' => 'Internet & WiFi', 'amount' => 420000, 'note' => 'Paket internet kasir dan CCTV.'],
            ['category' => 'Operasional', 'title' => 'ATK Operasional', 'amount' => 98000, 'note' => 'Pembelian kertas struk dan pulpen.'],
            ['category' => 'Operasional', 'title' => 'Biaya Kebersihan', 'amount' => 175000, 'note' => 'Sabun lantai, pel, dan tisu.'],
            ['category' => 'Inventaris', 'title' => 'Perawatan Peralatan', 'amount' => 260000, 'note' => 'Servis printer thermal kasir.'],
            ['category' => 'Marketing', 'title' => 'Promosi Online', 'amount' => 300000, 'note' => 'Iklan media sosial mingguan.'],
            ['category' => 'Operasional', 'title' => 'Biaya Keamanan', 'amount' => 220000, 'note' => 'Iuran keamanan area ruko.'],
            ['category' => 'Operasional', 'title' => 'Gas LPG', 'amount' => 210000, 'note' => 'Stok gas untuk area pantry.'],
            ['category' => 'Transport', 'title' => 'Transport Pembelian', 'amount' => 145000, 'note' => 'Ongkos kirim pembelian suplai.'],
            ['category' => 'Operasional', 'title' => 'Perlengkapan Kasir', 'amount' => 132000, 'note' => 'Tape, cutter, dan label harga.'],
            ['category' => 'Karyawan', 'title' => 'Gaji Karyawan Harian', 'amount' => 550000, 'note' => 'Pembayaran tenaga harian lepas.'],
        ];

        foreach ($expenseRows as $idx => $row) {
            Expense::query()->create([
                'user_id' => $users[$idx % $users->count()]->id,
                'category' => $row['category'],
                'title' => $row['title'],
                'amount' => $row['amount'],
                'date' => Carbon::today()->subDays($idx)->toDateString(),
                'note' => $row['note'],
            ]);
        }
    }
}
