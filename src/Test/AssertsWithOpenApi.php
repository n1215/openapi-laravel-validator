<?php

declare(strict_types=1);

namespace N1215\OpenApiValidation\Laravel\Test;

use BadMethodCallException;
use Illuminate\Support\Facades\Event;
use N1215\OpenApiValidation\Laravel\ValidatorsFactoryInterface;
use PHPUnit\Framework\TestCase;

trait AssertsWithOpenApi
{
    protected ?OpenApiAssertion $openApiAssertion = null;

    public function setOpenApiAssertion(string $path, string $method): void
    {
        if (! $this instanceof TestCase) {
            throw new BadMethodCallException(
                'trait ' . AssertsWithOpenApi::class .  ' should be used by a subclass of ' . TestCase::class
            );
        }

        if ($this->openApiAssertion === null) {
            /** @var ValidatorsFactoryInterface $validatorsFactory */
            $validatorsFactory = app()->make(ValidatorsFactoryInterface::class);
            $validators = $validatorsFactory->make();
            $this->openApiAssertion = new OpenApiAssertion(
                $validators,
                Event::getFacadeRoot(),
                $this
            );
            $this->openApiAssertion->listen();
        }

        $this->openApiAssertion->setOperationAddress($path, $method);
    }

    public function disableRequestAssertion(): void
    {
        if ($this->openApiAssertion === null) {
            throw new BadMethodCallException('assertion is not set.');
        }
        $this->openApiAssertion->disableRequestAssertion();
    }

    public function disableResponseAssertion(): void
    {
        if ($this->openApiAssertion === null) {
            throw new BadMethodCallException('assertion is not set.');
        }
        $this->openApiAssertion->disableResponseAssertion();
    }
}
