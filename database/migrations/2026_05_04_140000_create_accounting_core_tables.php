<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->id();
                $table->string('code', 32)->unique();
                $table->string('name', 120);
                $table->string('type', 32);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('journal_entries')) {
            Schema::create('journal_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('number', 80)->unique();
                $table->date('posted_at');
                $table->string('source_type', 120);
                $table->unsignedBigInteger('source_id');
                $table->string('event', 80);
                $table->string('memo', 255)->nullable();
                $table->string('status', 24)->default('posted');
                $table->timestamps();
                $table->unique(['source_type', 'source_id', 'event'], 'journal_source_event_unique');
                $table->index(['branch_id', 'posted_at']);
            });
        }

        if (! Schema::hasTable('journal_lines')) {
            Schema::create('journal_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
                $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
                $table->string('description', 255)->nullable();
                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);
                $table->timestamps();
                $table->index(['account_id']);
            });
        }

        $accounts = [
            ['code' => '1101', 'name' => 'Kas', 'type' => 'asset'],
            ['code' => '1102', 'name' => 'Bank', 'type' => 'asset'],
            ['code' => '1201', 'name' => 'Persediaan Barang Dagang', 'type' => 'asset'],
            ['code' => '1301', 'name' => 'PPN Masukan', 'type' => 'asset'],
            ['code' => '2101', 'name' => 'Hutang Usaha Supplier', 'type' => 'liability'],
            ['code' => '5101', 'name' => 'Harga Pokok Penjualan', 'type' => 'expense'],
            ['code' => '5201', 'name' => 'Beban Ongkir Pembelian', 'type' => 'expense'],
            ['code' => '5301', 'name' => 'Diskon Pembelian', 'type' => 'contra_expense'],
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
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
    }
};
