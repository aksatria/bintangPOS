<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            UserSeeder::class,
            PermissionSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            CustomerSeeder::class,
            StoreSettingSeeder::class,
            SaleSeeder::class,
            ExpenseSeeder::class,
            CustomerFollowUpSeeder::class,
            OperationalDemoSeeder::class,
            AuditLogDemoSeeder::class,
            ApprovalQueuePendingSeeder::class,
            ApprovalQueueResolvedSeeder::class,
        ]);
    }
}
