<?php

namespace Primo\PolicyEnforcer;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Primo\PolicyEnforcer\Http\Middleware\CentralPolicyEnforcer;

class PolicyEnforcerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/policy-enforcer.php',
            'policy-enforcer'
        );
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/policy-enforcer.php' => config_path('policy-enforcer.php'),
        ], 'policy-enforcer-config');

        if (!config('policy-enforcer.enabled')) {
            return;
        }

        $kernel = $this->app->make(Kernel::class);
        $kernel->pushMiddleware(CentralPolicyEnforcer::class);
    }
}
