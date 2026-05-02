<?php

namespace App\Providers;

use App\Models\Sale;
use App\Policies\SalePolicy;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Sale::class, SalePolicy::class);

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
