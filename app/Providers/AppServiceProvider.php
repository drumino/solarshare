<?php

namespace App\Providers;

use App\Models\Review;
use App\Observers\ReviewObserver;
use App\Policies\ReportPolicy;
use App\Policies\ReviewPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Gate::policy(Review::class, ReportPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Review::observe(ReviewObserver::class);
    }
}
