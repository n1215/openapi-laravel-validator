# openapi-laravel-validator
OpenAPI(v3) Validation for Laravel based on [n1215/openapi-http-foundation-validator](https://github.com/n1215/openapi-http-foundation-validator).

## Requirements
- PHP >= 8.1
- Laravel >= 9.0

## Installation

```shell
composer require n1215/openapi-laravel-validator
```

## Usage

### 1. create your OpenAPI Specification file
Create a YAML file or a JSON File.

example: [hello.yaml](./resource/hello.yaml)


### 2. create a Service Provider

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
use N1215\OpenApiValidation\HttpFoundation\ValidatorBuilder;
use N1215\OpenApiValidation\Laravel\OpenApiLaravelValidatorServiceProvider;

class OpenApiValidatorServiceProvider extends OpenApiLaravelValidatorServiceProvider
{
    protected function makeValidationBuilder(): ValidatorBuilder
    {
        $httpMessageFactory = $this->makeHttpMessageFactory();
        return (new ValidatorBuilder($httpMessageFactory))
            ->fromYamlFile('/path/to/your-definition.yaml')
            ->setSimpleCache(Cache::store(), 3600);
    }
}
```

### 3. use `AssertsWithOpenApi` trait in HTTP tests
Simply use the trait to automatically validate requests and responses.

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use N1215\OpenApiValidation\Laravel\Test\AssertsWithOpenApi;

class GetHelloTest extends TestCase
{
    use AssertsWithOpenApi;

    public function testSuccess(): void
    {
        $response = $this->json(
            'get',
            '/hello?name=Taro'
        );

        $response->assertOk();
        $response->assertJson(['message' => 'Hello, Taro']);
    }

    public function testValidationFailed(): void
    {
        // disable request validation for invalid request parameters
        $this->disableRequestAssertion();

        $response = $this->json(
            'get',
            '/hello'
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name' => 'The name field is required']);
    }
}
```

### 4. use `ValidateWithOpenAPi` Middleware

You can change responses as you like.

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use N1215\OpenApiValidation\RequestValidationFailed;
use N1215\OpenApiValidation\ResponseValidationFailed;
use Symfony\Component\HttpFoundation\Response;

class ValidateWithOpenApi extends \N1215\OpenApiValidation\Laravel\Middleware\ValidateWithOpenApi
{
    protected function makeRequestValidationFailedResponse(RequestValidationFailed $e): Response
    {
        return $this->responseFactory->json(
            [
                'message' =>  'failed to validate request',
            ],
            Response::HTTP_BAD_REQUEST
        );
    }

    protected function makeResponseValidationFailedResponse(ResponseValidationFailed $e): Response
    {
        return $this->responseFactory->json(
            [
                'message' =>  'failed to validate response',
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
```
