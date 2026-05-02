<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerFollowUp;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerFollowUpSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::query()->orderBy('id')->take(8)->get();
        if ($customers->isEmpty()) {
            return;
        }

        $owner = User::query()->whereIn('role', ['owner', 'admin'])->first();

        $payload = [
            ['action_type' => 'reminder', 'status' => 'baru', 'note' => 'Follow-up telepon ulang untuk konfirmasi pesanan.', 'reminder_at' => now()->subHours(6), 'reminded_at' => null],
            ['action_type' => 'message', 'status' => 'proses', 'note' => 'Kirim promo bundling via chat.', 'reminder_at' => now()->subHours(2), 'reminded_at' => null],
            ['action_type' => 'note', 'status' => 'selesai', 'note' => 'Sudah dihubungi, customer akan belanja akhir pekan.', 'reminder_at' => now()->subDay(), 'reminded_at' => now()->subDay(), 'completed_at' => now()->subDay()],
            ['action_type' => 'reminder', 'status' => 'gagal', 'note' => 'Nomor tidak aktif, perlu update kontak.', 'reminder_at' => now()->subDays(2), 'reminded_at' => now()->subDays(2)],
            ['action_type' => 'reminder', 'status' => 'baru', 'note' => 'Jadwal follow-up untuk penawaran repeat order.', 'reminder_at' => now()->addHours(4), 'reminded_at' => null],
            ['action_type' => 'message', 'status' => 'proses', 'note' => 'Tunggu balasan customer terkait stok baru.', 'reminder_at' => now()->addDay(), 'reminded_at' => null],
        ];

        foreach ($customers as $index => $customer) {
            $row = $payload[$index % count($payload)];
            CustomerFollowUp::query()->create([
                'customer_id' => $customer->id,
                'created_by' => $owner?->id,
                'action_type' => $row['action_type'],
                'status' => $row['status'],
                'note' => $row['note'],
                'reminder_at' => $row['reminder_at'],
                'reminded_at' => $row['reminded_at'],
                'completed_at' => $row['completed_at'] ?? null,
            ]);
        }
    }
}

