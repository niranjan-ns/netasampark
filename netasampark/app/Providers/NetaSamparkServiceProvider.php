<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class NetaSamparkServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register custom configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/custom/netasampark.php', 'netasampark'
        );

        // Register custom services
        $this->app->singleton('netasampark.organization', function ($app) {
            return new \App\Services\OrganizationService();
        });

        $this->app->singleton('netasampark.messaging', function ($app) {
            return new \App\Services\MessagingService();
        });

        $this->app->singleton('netasampark.compliance', function ($app) {
            return new \App\Services\ComplianceService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load custom configuration
        $this->publishes([
            __DIR__.'/../../config/custom/netasampark.php' => config_path('netasampark.php'),
        ], 'netasampark-config');

        // Set default string length for database
        Schema::defaultStringLength(191);

        // Disable mass assignment protection in development
        if (config('app.debug')) {
            Model::unguard();
        }

        // Register custom Blade directives
        $this->registerBladeDirectives();

        // Register custom gates
        $this->registerGates();

        // Register custom routes
        $this->registerRoutes();

        // Register custom middleware
        $this->registerMiddleware();
    }

    /**
     * Register custom Blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        Blade::directive('organization', function ($expression) {
            return "<?php echo auth()->user()->organization->name ?? 'Unknown'; ?>";
        });

        Blade::directive('feature', function ($expression) {
            return "<?php if (config('netasampark.features.{$expression}')): ?>";
        });

        Blade::directive('endfeature', function () {
            return "<?php endif; ?>";
        });

        Blade::directive('compliance', function ($expression) {
            return "<?php if (config('netasampark.compliance.{$expression}.enabled')): ?>";
        });

        Blade::directive('endcompliance', function () {
            return "<?php endif; ?>";
        });
    }

    /**
     * Register custom authorization gates.
     */
    protected function registerGates(): void
    {
        Gate::define('manage-organization', function ($user) {
            return in_array($user->role, ['owner', 'admin']);
        });

        Gate::define('manage-users', function ($user) {
            return in_array($user->role, ['owner', 'admin', 'manager']);
        });

        Gate::define('manage-voters', function ($user) {
            return in_array($user->role, ['owner', 'admin', 'manager', 'agent']);
        });

        Gate::define('manage-campaigns', function ($user) {
            return in_array($user->role, ['owner', 'admin', 'manager']);
        });

        Gate::define('view-analytics', function ($user) {
            return in_array($user->role, ['owner', 'admin', 'manager', 'analyst']);
        });

        Gate::define('manage-finance', function ($user) {
            return in_array($user->role, ['owner', 'admin']);
        });

        Gate::define('manage-compliance', function ($user) {
            return in_array($user->role, ['owner', 'admin']);
        });
    }

    /**
     * Register custom routes.
     */
    protected function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])->group(function () {
            // Organization management routes
            Route::prefix('organization')->group(function () {
                Route::get('/settings', [\App\Http\Controllers\OrganizationController::class, 'settings'])->name('organization.settings');
                Route::put('/settings', [\App\Http\Controllers\OrganizationController::class, 'updateSettings'])->name('organization.update-settings');
                Route::get('/billing', [\App\Http\Controllers\OrganizationController::class, 'billing'])->name('organization.billing');
            });

            // Voter management routes
            Route::prefix('voters')->group(function () {
                Route::get('/', [\App\Http\Controllers\VoterController::class, 'index'])->name('voters.index');
                Route::post('/', [\App\Http\Controllers\VoterController::class, 'store'])->name('voters.store');
                Route::get('/{voter}', [\App\Http\Controllers\VoterController::class, 'show'])->name('voters.show');
                Route::put('/{voter}', [\App\Http\Controllers\VoterController::class, 'update'])->name('voters.update');
                Route::delete('/{voter}', [\App\Http\Controllers\VoterController::class, 'destroy'])->name('voters.destroy');
                Route::post('/import', [\App\Http\Controllers\VoterController::class, 'import'])->name('voters.import');
                Route::get('/export', [\App\Http\Controllers\VoterController::class, 'export'])->name('voters.export');
            });

            // Campaign management routes
            Route::prefix('campaigns')->group(function () {
                Route::get('/', [\App\Http\Controllers\CampaignController::class, 'index'])->name('campaigns.index');
                Route::post('/', [\App\Http\Controllers\CampaignController::class, 'store'])->name('campaigns.store');
                Route::get('/{campaign}', [\App\Http\Controllers\CampaignController::class, 'show'])->name('campaigns.show');
                Route::put('/{campaign}', [\App\Http\Controllers\CampaignController::class, 'update'])->name('campaigns.update');
                Route::delete('/{campaign}', [\App\Http\Controllers\CampaignController::class, 'destroy'])->name('campaigns.destroy');
                Route::post('/{campaign}/send', [\App\Http\Controllers\CampaignController::class, 'send'])->name('campaigns.send');
                Route::get('/{campaign}/analytics', [\App\Http\Controllers\CampaignController::class, 'analytics'])->name('campaigns.analytics');
            });

            // Communication routes
            Route::prefix('communication')->group(function () {
                Route::get('/inbox', [\App\Http\Controllers\CommunicationController::class, 'inbox'])->name('communication.inbox');
                Route::get('/templates', [\App\Http\Controllers\CommunicationController::class, 'templates'])->name('communication.templates');
                Route::post('/templates', [\App\Http\Controllers\CommunicationController::class, 'storeTemplate'])->name('communication.store-template');
                Route::get('/analytics', [\App\Http\Controllers\CommunicationController::class, 'analytics'])->name('communication.analytics');
            });

            // Analytics routes
            Route::prefix('analytics')->group(function () {
                Route::get('/', [\App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics.index');
                Route::get('/voters', [\App\Http\Controllers\AnalyticsController::class, 'voters'])->name('analytics.voters');
                Route::get('/campaigns', [\App\Http\Controllers\AnalyticsController::class, 'campaigns'])->name('analytics.campaigns');
                Route::get('/finance', [\App\Http\Controllers\AnalyticsController::class, 'finance'])->name('analytics.finance');
                Route::get('/export', [\App\Http\Controllers\AnalyticsController::class, 'export'])->name('analytics.export');
            });

            // Finance routes
            Route::prefix('finance')->group(function () {
                Route::get('/', [\App\Http\Controllers\FinanceController::class, 'index'])->name('finance.index');
                Route::get('/expenses', [\App\Http\Controllers\FinanceController::class, 'expenses'])->name('finance.expenses');
                Route::post('/expenses', [\App\Http\Controllers\FinanceController::class, 'storeExpense'])->name('finance.store-expense');
                Route::get('/reports', [\App\Http\Controllers\FinanceController::class, 'reports'])->name('finance.reports');
            });

            // Support routes
            Route::prefix('support')->group(function () {
                Route::get('/tickets', [\App\Http\Controllers\SupportController::class, 'tickets'])->name('support.tickets');
                Route::post('/tickets', [\App\Http\Controllers\SupportController::class, 'storeTicket'])->name('support.store-ticket');
                Route::get('/tickets/{ticket}', [\App\Http\Controllers\SupportController::class, 'showTicket'])->name('support.show-ticket');
                Route::put('/tickets/{ticket}', [\App\Http\Controllers\SupportController::class, 'updateTicket'])->name('support.update-ticket');
                Route::get('/knowledge-base', [\App\Http\Controllers\SupportController::class, 'knowledgeBase'])->name('support.knowledge-base');
            });
        });
    }

    /**
     * Register custom middleware.
     */
    protected function registerMiddleware(): void
    {
        $this->app['router']->aliasMiddleware('organization.access', \App\Http\Middleware\OrganizationAccessMiddleware::class);
        $this->app['router']->aliasMiddleware('subscription.active', \App\Http\Middleware\SubscriptionActiveMiddleware::class);
        $this->app['router']->aliasMiddleware('feature.enabled', \App\Http\Middleware\FeatureEnabledMiddleware::class);
        $this->app['router']->aliasMiddleware('compliance.check', \App\Http\Middleware\ComplianceCheckMiddleware::class);
    }
}
