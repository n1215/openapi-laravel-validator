<?php

declare(strict_types=1);

namespace N1215\OpenApiValidation\Laravel;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use N1215\OpenApiValidation\HttpFoundation\ValidatorBuilder;
use N1215\OpenApiValidation\Laravel\Middleware\ValidateWithOpenApi;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;

class OpenApiLaravelValidatorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(ValidatorsFactoryInterface::class, function () {
            return new ValidatorsFactory($this->makeValidationBuilder());
        });

        $this->app->singleton(ValidateWithOpenApi::class);
    }

    protected function makeValidationBuilder(): ValidatorBuilder
    {
        $httpMessageFactory = $this->makeHttpMessageFactory();
        return (new ValidatorBuilder($httpMessageFactory))
            ->fromYamlFile(__DIR__ . '/../resource/hello.yaml')
            ->setSimpleCache(Cache::store(), 3600);
    }

    protected function makeHttpMessageFactory(): HttpMessageFactoryInterface
    {
        $psr17Factory = new Psr17Factory();
        return new PsrHttpFactory(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );
    }
}
