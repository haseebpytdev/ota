<?php

namespace App\Providers;

use App\Models\Agency;
use App\Models\AgencyCommunicationSetting;
use App\Models\AgencyMedia;
use App\Models\AgencyMessageTemplate;
use App\Models\Agent;
use App\Models\AgentCommissionEntry;
use App\Models\AgentCommissionStatement;
use App\Models\Booking;
use App\Models\BookingCancellationRequest;
use App\Models\BookingDocument;
use App\Models\BookingRefund;
use App\Models\MarkupRule;
use App\Models\StaffProfile;
use App\Models\SupplierConnection;
use App\Models\User;
use App\Policies\AgencyBrandingPolicy;
use App\Policies\AgencyCommunicationSettingPolicy;
use App\Policies\AgencyMediaPolicy;
use App\Policies\AgencyMessageTemplatePolicy;
use App\Policies\AgentCommissionPolicy;
use App\Policies\AgentPolicy;
use App\Policies\BookingCancellationPolicy;
use App\Policies\BookingDocumentPolicy;
use App\Policies\BookingPolicy;
use App\Policies\BookingRefundPolicy;
use App\Policies\MarkupRulePolicy;
use App\Policies\StaffProfilePolicy;
use App\Policies\SupplierConnectionPolicy;
use App\Policies\UserManagementPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        RateLimiter::for('lookup-booking', fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('guest-token', fn (Request $request): Limit => Limit::perMinute(15)->by($request->ip()));
        RateLimiter::for('public-booking-submit', fn (Request $request): Limit => Limit::perMinute(12)->by($request->ip()));
        RateLimiter::for('payment-proof-submit', fn (Request $request): Limit => Limit::perMinute(20)->by((string) ($request->user()?->id ?? $request->ip())));

        Gate::policy(Agency::class, AgencyBrandingPolicy::class);
        Gate::policy(AgencyCommunicationSetting::class, AgencyCommunicationSettingPolicy::class);
        Gate::policy(AgencyMedia::class, AgencyMediaPolicy::class);
        Gate::policy(AgencyMessageTemplate::class, AgencyMessageTemplatePolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(BookingCancellationRequest::class, BookingCancellationPolicy::class);
        Gate::policy(BookingRefund::class, BookingRefundPolicy::class);
        Gate::policy(BookingDocument::class, BookingDocumentPolicy::class);
        Gate::policy(Agent::class, AgentPolicy::class);
        Gate::policy(AgentCommissionEntry::class, AgentCommissionPolicy::class);
        Gate::policy(AgentCommissionStatement::class, AgentCommissionPolicy::class);
        Gate::policy(StaffProfile::class, StaffProfilePolicy::class);
        Gate::policy(MarkupRule::class, MarkupRulePolicy::class);
        Gate::policy(SupplierConnection::class, SupplierConnectionPolicy::class);
        Gate::policy(User::class, UserManagementPolicy::class);

        Gate::define('commission.adjust', [AgentCommissionPolicy::class, 'adjust']);
        Gate::define('commission.payout', [AgentCommissionPolicy::class, 'payout']);
        Gate::define('commission.statement', [AgentCommissionPolicy::class, 'statement']);
    }
}
