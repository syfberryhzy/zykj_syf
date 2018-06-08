<?php

namespace App\Providers;

use App\Models\Address;
use App\Policies\AddressPolicy;
use App\Models\Coupon;
use App\Policies\CouponPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // Address::class => AddressPolicy::class,
        // Coupon::class => CouponPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
