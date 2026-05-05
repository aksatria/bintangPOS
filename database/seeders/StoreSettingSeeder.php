<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class StoreSettingSeeder extends Seeder
{
    public function run(): void
    {
        StoreSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Bintang Raya',
                'address' => 'Jl. Niaga Raya No. 88, Jakarta Selatan',
                'whatsapp' => '0812-3456-7890',
                'receipt_footer' => 'Terima kasih telah berbelanja. Barang yang sudah dibeli tidak dapat ditukar kecuali ada cacat produksi.',
                'cash_rounding_enabled' => true,
                'cash_rounding_step' => 100,
                'quick_pay_presets' => [10000, 20000, 50000, 100000],
                'payment_admin_fee_rules' => [
                    'qris' => ['type' => 'percent', 'value' => 0.7],
                    'debit' => ['type' => 'percent', 'value' => 1.0],
                    'e_wallet' => ['type' => 'percent', 'value' => 1.0],
                ],
                'payment_overpay_rules' => [
                    'cash' => ['max_overpay' => 500000],
                    'qris' => ['max_overpay' => 0],
                    'debit' => ['max_overpay' => 0],
                    'transfer' => ['max_overpay' => 0],
                    'e_wallet' => ['max_overpay' => 0],
                ],
                'pending_non_cash_timeout_minutes' => 30,
                'promo_buy_x_get_y_rules' => [
                    ['sku' => 'SKU-DEMO-1', 'buy_qty' => 2, 'get_qty' => 1, 'active' => false],
                ],
            ]
        );
    }
}
