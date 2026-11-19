<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Billing\Services\CreditService;
use App\Domain\Billing\Services\MoneyService;
use App\Domain\Generation\Models\Generation;
use App\Infrastructure\Pixelforge\Client\PixelforgeClient;
use App\Infrastructure\Pixelforge\Contracts\ImageGenerationProviderInterface;
use App\Infrastructure\Pixelforge\FakePixelforgeProvider;
use App\Infrastructure\Pixelforge\PixelforgeProvider;
use App\Policies\GenerationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PixelforgeClient::class, function (): PixelforgeClient {
            return new PixelforgeClient(
                apiKey:     config('services.pixelforge.api_key'),
                baseUrl:    config('services.pixelforge.api_url'),
            );
        });

        $this->app->singleton(
            ImageGenerationProviderInterface::class,
            function (): ImageGenerationProviderInterface {
                if (config('services.pixelforge.driver') === 'fake') {
                    return new FakePixelforgeProvider(
                        webhookSecret: config('services.pixelforge.webhook_secret', ''),
                    );
                }

                return new PixelforgeProvider(
                    client:        $this->app->make(PixelforgeClient::class),
                    webhookSecret: config('services.pixelforge.webhook_secret'),
                );
            }
        );

        $this->app->singleton(MoneyService::class, function (): MoneyService {
            return new MoneyService(
                markupPercent: (int) config('services.pixelforge.markup_percentage', 25),
            );
        });

        $this->app->singleton(CreditService::class);
    }

    public function boot(): void
    {
        Gate::policy(Generation::class, GenerationPolicy::class);
    }
}
