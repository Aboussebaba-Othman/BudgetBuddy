<?php

namespace App\Providers;

use App\Models\Expense;
use App\Models\Tag;
use App\Models\Group;
use App\Policies\ExpensePolicy;
use App\Policies\TagPolicy;
use App\Policies\GroupPolicy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\GroupExpense;
use App\Policies\GroupExpensePolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Définir les politiques
        Gate::policy(Expense::class, ExpensePolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(Group::class, GroupPolicy::class);
        Gate::policy(GroupExpense::class, GroupExpensePolicy::class);

    }
}