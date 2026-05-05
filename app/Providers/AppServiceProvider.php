<?php

namespace App\Providers;

use App\Models\Sale;
use App\Models\ApprovalRequest;
use App\Models\SupplierPurchase;
use App\Policies\SalePolicy;
use App\Support\ActiveBranchContext;
use Illuminate\Pagination\Paginator;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.tailwind');
        Paginator::defaultSimpleView('vendor.pagination.simple-tailwind');

        Gate::policy(Sale::class, SalePolicy::class);

        if (! $this->app->runningInConsole()) {
            $host = request()->getHost();
            if (is_string($host) && str_ends_with($host, '.ngrok-free.dev')) {
                URL::forceScheme('https');
            }

            View::composer('layouts.navigation', function ($view): void {
                $user = auth()->user();
                if (! $user) {
                    $view->with('navigationBadges', []);
                    return;
                }

                $branchId = ActiveBranchContext::resolveBranchId($user);
                $approvalQuery = ApprovalRequest::query()
                    ->where('status', 'pending')
                    ->whereIn('type', ['supplier.purchase_approval', 'supplier.purchase_payment']);
                $purchaseQuery = SupplierPurchase::query()
                    ->where('remaining_amount', '>', 0)
                    ->whereIn('payment_status', ['unpaid', 'partial', 'overdue']);

                if (! $user->hasAnyRole(['owner'])) {
                    $approvalQuery->where('branch_id', $branchId);
                    $purchaseQuery->where('branch_id', $branchId);
                } elseif ($branchId) {
                    $approvalQuery->where('branch_id', $branchId);
                    $purchaseQuery->where('branch_id', $branchId);
                }

                $view->with('navigationBadges', [
                    'supplier_pending_approvals' => (int) (clone $approvalQuery)->count(),
                    'supplier_overdue_debts' => (int) (clone $purchaseQuery)->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString())->count(),
                    'supplier_open_debts' => (int) (clone $purchaseQuery)->count(),
                ]);
            });
        }

        if (! $this->app->runningInConsole()) {
            return;
        }

        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            if ($this->app->environment('testing')
                && filter_var(env('ENABLE_DESTRUCTIVE_GUARD_IN_TESTS', false), FILTER_VALIDATE_BOOL) !== true) {
                return;
            }

            $command = $this->resolveCommandName($event);
            if (! in_array($command, ['migrate:fresh', 'db:wipe', 'migrate:refresh'], true)) {
                return;
            }

            if (! $this->isProtectedEnvironment()) {
                if ($this->destructiveCommandsAllowed($event)) {
                    return;
                }

                throw new RuntimeException(
                    "Command '{$command}' butuh 2-step confirmation. ".
                    "Jalankan dengan --force dan set ALLOW_DESTRUCTIVE_COMMANDS=true."
                );
            }

            throw new RuntimeException(
                "Command '{$command}' diblokir oleh safety guard proyek. ".
                "Environment non-dev tidak boleh menjalankan command destruktif ini."
            );
        });
    }

    private function resolveCommandName(CommandStarting $event): string
    {
        $name = (string) ($event->command ?? '');
        if ($name !== '') {
            return trim($name);
        }

        $firstArg = $event->input?->getFirstArgument();
        return is_string($firstArg) ? trim($firstArg) : '';
    }

    private function destructiveCommandsAllowed(CommandStarting $event): bool
    {
        $flag = filter_var(env('ALLOW_DESTRUCTIVE_COMMANDS', false), FILTER_VALIDATE_BOOL) === true;
        $forced = (bool) ($event->input?->hasParameterOption('--force') ?? false);

        return $flag && $forced;
    }

    private function isProtectedEnvironment(): bool
    {
        return ! $this->app->environment(['local', 'development', 'testing']);
    }
}
