<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\Asset;
use App\Models\Attachment;
use App\Models\CardTransaction;
use App\Models\Category;
use App\Models\Contribution;
use App\Models\CreditCard;
use App\Models\GroupSetting;
use App\Models\Invitation;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Models\User;
use App\Observers\AccountObserver;
use App\Observers\AssetObserver;
use App\Observers\CardTransactionObserver;
use App\Observers\CategoryObserver;
use App\Observers\ContributionObserver;
use App\Observers\CreditCardObserver;
use App\Observers\GroupSettingObserver;
use App\Observers\InvitationObserver;
use App\Observers\PortfolioObserver;
use App\Observers\TransactionObserver;
use App\Observers\UserObserver;
use App\Policies\AccountPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CreditCardPolicy;
use App\Policies\TransactionPolicy;
use App\Support\Audit;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Se o mailer SMTP não tem credenciais (dev sem config), cai no driver "log"
        // para envios não quebrarem a aplicação (ex.: recuperar senha, convites).
        if (config('mail.default') === 'smtp'
            && (empty(config('mail.mailers.smtp.username')) || empty(config('mail.mailers.smtp.password')))) {
            config(['mail.default' => 'log']);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Garante a localização pt-BR em toda a aplicação (independe de env/cache antigo).
        App::setLocale('pt_BR');
        Carbon::setLocale('pt_BR');

        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(Account::class, AccountPolicy::class);
        Gate::policy(CreditCard::class, CreditCardPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);

        // Trilha de auditoria: observa todas as entidades relevantes.
        Transaction::observe(TransactionObserver::class);
        Account::observe(AccountObserver::class);
        CreditCard::observe(CreditCardObserver::class);
        CardTransaction::observe(CardTransactionObserver::class);
        Category::observe(CategoryObserver::class);
        Portfolio::observe(PortfolioObserver::class);
        Asset::observe(AssetObserver::class);
        Contribution::observe(ContributionObserver::class);
        Invitation::observe(InvitationObserver::class);
        User::observe(UserObserver::class);
        GroupSetting::observe(GroupSettingObserver::class);

        // Login/logout também entram na trilha.
        Event::listen(Login::class, fn (Login $event) => Audit::log('Entrou no sistema', 'login', null, $event->user));
        Event::listen(Logout::class, fn (Logout $event) => Audit::log('Encerrou a sessão', 'logout', null, $event->user));

        Attachment::deleting(function (Attachment $attachment) {
            Storage::disk('local')->delete($attachment->path);
        });
    }
}
