<?php

namespace App\Providers;

use App\Domain\Contracts\ApplicationEnvironmentPort;
use App\Domain\Contracts\ApplicationLoggerPort;
use App\Domain\Contracts\AuditLoggerPort;
use App\Domain\Contracts\AuthLifecycleEventsPort;
use App\Domain\Contracts\ConfigCachePort;
use App\Domain\Contracts\EnvFileEditorPort;
use App\Domain\Contracts\LoginRateLimiterPort;
use App\Domain\Contracts\MarketDataProviderManagerPort;
use App\Domain\Contracts\PasswordHasherPort;
use App\Domain\Contracts\PasswordResetBrokerPort;
use App\Domain\Contracts\QuotationCollectCommandRunnerPort;
use App\Domain\Contracts\QuotationCollectExecutionLoggerPort;
use App\Domain\Contracts\QuotationDeletionRepositoryPort;
use App\Domain\Contracts\QuotationPersistencePort;
use App\Domain\Contracts\QuotationQueryBuilderPort;
use App\Domain\Contracts\QuotationReconciliationRepositoryPort;
use App\Domain\Contracts\QuotationsConfigPort;
use App\Domain\Contracts\QuoteCachePort;
use App\Domain\Contracts\RememberTokenGeneratorPort;
use App\Domain\Contracts\UserRepositoryPort;
use App\Domain\Contracts\WebSessionAuthenticatorPort;
use App\Domain\Contracts\WebSessionStatePort;
use App\Domain\MarketData\AssetTypeResolver;
use App\Domain\MarketData\SymbolNormalizer;
use App\Domain\Quotations\QuotationQualityService;
use App\Infrastructure\Audit\AuditLogger;
use App\Infrastructure\Auth\AuthLifecycleEvents;
use App\Infrastructure\Auth\LoginRateLimiter;
use App\Infrastructure\Auth\PasswordHasher;
use App\Infrastructure\Auth\PasswordResetBroker;
use App\Infrastructure\Auth\RememberTokenGenerator;
use App\Infrastructure\Auth\UserRepository;
use App\Infrastructure\Auth\WebSessionAuthenticator;
use App\Infrastructure\Auth\WebSessionState;
use App\Infrastructure\Config\ApplicationEnvironment;
use App\Infrastructure\Config\ConfigCacheManager;
use App\Infrastructure\Config\EnvFileEditor;
use App\Infrastructure\Config\QuotationsConfig;
use App\Infrastructure\Console\QuotationCollectCommandRunner;
use App\Infrastructure\MarketData\MarketDataProviderManager;
use App\Infrastructure\MarketData\QuoteCache;
use App\Infrastructure\Observability\ApplicationLogger;
use App\Infrastructure\Observability\QuotationCollectExecutionLogger;
use App\Infrastructure\Quotations\QuotationDeletionRepository;
use App\Infrastructure\Quotations\QuotationPersistenceGateway;
use App\Infrastructure\Quotations\QuotationQueryBuilder;
use App\Infrastructure\Quotations\QuotationReconciliationRepository;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra configuracoes e dependencias necessarias.
     */
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerDomainServices();
        $this->registerPorts();
    }

    /**
     * Registra singletons de servicos de dominio puros e utilitarios core.
     */
    private function registerDomainServices(): void
    {
        $this->app->singleton(MarketDataProviderManager::class, function ($container) {
            return new MarketDataProviderManager(
                $container,
                static fn (): array => config('market-data', [])
            );
        });

        $this->app->singleton(AssetTypeResolver::class, function () {
            return new AssetTypeResolver(
                config('market-data.crypto_symbols', []),
                config('market-data.currency_codes', [])
            );
        });

        $this->app->singleton(SymbolNormalizer::class);

        $this->app->singleton(QuotationQualityService::class, function () {
            return new QuotationQualityService(
                outlierGuardEnabled: (bool) config('quotations.quality.outlier_guard.enabled', true),
                configuredMinReferencePoints: (int) config('quotations.quality.outlier_guard.min_reference_points', 4),
                configuredWindowSize: (int) config('quotations.quality.outlier_guard.window_size', 20),
                configuredMaxDeviationRatio: (float) config('quotations.quality.outlier_guard.max_deviation_ratio', 0.85),
            );
        });
    }

    /**
     * Registra adaptadores de infraestrutura para as portas de dominio.
     */
    private function registerPorts(): void
    {
        $bindings = [
            ApplicationEnvironmentPort::class => ApplicationEnvironment::class,
            ApplicationLoggerPort::class => ApplicationLogger::class,
            AuditLoggerPort::class => AuditLogger::class,
            AuthLifecycleEventsPort::class => AuthLifecycleEvents::class,
            ConfigCachePort::class => ConfigCacheManager::class,
            EnvFileEditorPort::class => EnvFileEditor::class,
            LoginRateLimiterPort::class => LoginRateLimiter::class,
            PasswordHasherPort::class => PasswordHasher::class,
            PasswordResetBrokerPort::class => PasswordResetBroker::class,
            QuotationCollectCommandRunnerPort::class => QuotationCollectCommandRunner::class,
            QuotationCollectExecutionLoggerPort::class => QuotationCollectExecutionLogger::class,
            QuotationDeletionRepositoryPort::class => QuotationDeletionRepository::class,
            QuotationPersistencePort::class => QuotationPersistenceGateway::class,
            QuotationQueryBuilderPort::class => QuotationQueryBuilder::class,
            QuotationReconciliationRepositoryPort::class => QuotationReconciliationRepository::class,
            QuotationsConfigPort::class => QuotationsConfig::class,
            QuoteCachePort::class => QuoteCache::class,
            RememberTokenGeneratorPort::class => RememberTokenGenerator::class,
            UserRepositoryPort::class => UserRepository::class,
            WebSessionAuthenticatorPort::class => WebSessionAuthenticator::class,
            WebSessionStatePort::class => WebSessionState::class,
        ];

        foreach ($bindings as $port => $adapter) {
            $this->app->bind($port, fn (Container $container): mixed => $container->make($adapter));
        }

        // Reutiliza o singleton do manager para todas as injeções via porta.
        $this->app->alias(MarketDataProviderManager::class, MarketDataProviderManagerPort::class);
    }

    /**
     * Executa configuracoes na inicializacao da aplicacao.
     */
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
