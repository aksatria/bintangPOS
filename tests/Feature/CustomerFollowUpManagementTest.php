<?php

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\CustomerFollowUp;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

test('owner can access follow-up queue page', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner->value]);
    $this->actingAs($owner)->get(route('customers.followups'))->assertOk();
});

test('kasir cannot access follow-up queue page', function () {
    $cashier = User::factory()->create(['role' => UserRole::Cashier->value]);
    $this->actingAs($cashier)->get(route('customers.followups'))->assertForbidden();
});

test('quick follow-up creates record and can be progressed to selesai', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner->value]);
    $customer = Customer::query()->create(['name' => 'Followup Customer', 'phone' => '0891110001', 'is_active' => true]);

    $this->actingAs($owner)->post(route('customers.quick-followup', $customer), [
        'action_type' => 'reminder',
        'note' => 'Hubungi sore ini',
        'reminder_at' => now()->addHour()->toDateTimeString(),
    ])->assertRedirect();

    $followup = CustomerFollowUp::query()->latest('id')->first();
    expect($followup)->not->toBeNull();
    expect($followup->customer_id)->toBe($customer->id);
    expect($followup->status)->toBe('baru');

    $this->actingAs($owner)->patch(route('customers.followups.status', $followup), [
        'status' => 'selesai',
    ])->assertRedirect();

    expect($followup->fresh()->status)->toBe('selesai');
    expect($followup->fresh()->completed_at)->not->toBeNull();
});

test('follow-up reminder command marks reminded_at when telegram send success', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    putenv('TELEGRAM_BOT_TOKEN=test-bot-token');
    putenv('TELEGRAM_CHAT_ID=123456');

    StoreSetting::query()->create([
        'name' => 'Toko Uji',
        'address' => '-',
        'whatsapp' => null,
        'receipt_footer' => '-',
        'telegram_enabled' => true,
    ]);

    $owner = User::factory()->create(['role' => UserRole::Owner->value]);
    $customer = Customer::query()->create(['name' => 'Reminder Customer', 'phone' => '0891110002', 'is_active' => true]);

    $followup = CustomerFollowUp::query()->create([
        'customer_id' => $customer->id,
        'created_by' => $owner->id,
        'action_type' => 'reminder',
        'status' => 'baru',
        'note' => 'Reminder test',
        'reminder_at' => now()->subMinutes(10),
    ]);

    Artisan::call('telegram:send-followup-reminders');

    expect($followup->fresh()->reminded_at)->not->toBeNull();
});

