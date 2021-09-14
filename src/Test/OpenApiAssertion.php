<?php

declare(strict_types=1);

namespace N1215\OpenApiValidation\Laravel\Test;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Http\Events\RequestHandled;
use LogicException;
use N1215\OpenApiValidation\OperationAddress;
use N1215\OpenApiValidation\HttpFoundation\Validators;
use N1215\OpenApiValidation\RequestValidationFailed;
use N1215\OpenApiValidation\ResponseValidationFailed;
use PHPUnit\Framework\TestCase;

class OpenApiAssertion
{
    protected Validators $validators;

    protected Dispatcher $eventDispatcher;

    protected TestCase $testCase;

    protected ?OperationAddress $operationAddress;

    public function __construct(
        Validators $validators,
        Dispatcher $eventDispatcher,
        TestCase $testCase
    ) {
        $this->testCase = $testCase;
        $this->eventDispatcher = $eventDispatcher;
        $this->validators = $validators;
        $this->operationAddress = null;
    }

    /**
     * @return $this
     */
    public function listen(): self
    {
        $this->eventDispatcher->listen(
            RequestHandled::class,
            function (RequestHandled $event) {
                if ($this->operationAddress === null) {
                    throw new LogicException('please set operation address');
                }
                try {
                    $this->validators->getRequestValidator()->validate($event->request);
                    $this->testCase->assertTrue(true);
                } catch (RequestValidationFailed $e) {
                    $this->testCase->fail((string) $e);
                }

                if ($event->response->getStatusCode() >= 500) {
                    return;
                }

                try {
                    $this->validators->getResponseValidator()->validate(
                        $this->operationAddress,
                        $event->response
                    );
                    $this->testCase->assertTrue(true);
                } catch (ResponseValidationFailed $e) {
                    $this->testCase->fail((string) $e);
                }
            }
        );
        return $this;
    }

    /**
     * @return $this
     */
    public function setOperationAddress(string $path, string $method): self
    {
        $this->operationAddress = new OperationAddress($path, $method);
        return $this;
    }

    public function disableRequestAssertion(): void
    {
        $this->validators->getRequestValidator()->enable(false);
    }

    public function disableResponseAssertion(): void
    {
        $this->validators->getResponseValidator()->enable(false);
    }
}
