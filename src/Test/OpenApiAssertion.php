<?php

declare(strict_types=1);

namespace N1215\OpenApiValidation\Laravel\Test;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use League\OpenAPIValidation\PSR7\OperationAddress as LeagueOperationAddress;
use League\OpenAPIValidation\PSR7\PathFinder;
use N1215\OpenApiValidation\HttpFoundation\Validators;
use N1215\OpenApiValidation\OperationAddress;
use N1215\OpenApiValidation\RequestValidationFailed;
use N1215\OpenApiValidation\ResponseValidationFailed;
use PHPUnit\Framework\Assert;

class OpenApiAssertion
{
    public function __construct(
        protected readonly Validators $validators,
        protected readonly Dispatcher $eventDispatcher,
        protected readonly Assert $assert
    ) {
    }

    /**
     * @return $this
     */
    public function listen(): self
    {
        $this->eventDispatcher->listen(
            RequestHandled::class,
            function (RequestHandled $event) {
                try {
                    $this->validators->getRequestValidator()->validate($event->request);
                    $this->assert->assertTrue(true);
                } catch (RequestValidationFailed $e) {
                    $this->assert->fail((string) $e);
                }

                if ($event->response->getStatusCode() >= 500) {
                    return;
                }

                try {
                    $operationAddresses = $this->findManyOperationAddressesOrFail($event->request);
                    $this->validators->getResponseValidator()->validate(
                        $operationAddresses[0],
                        $event->response
                    );
                    $this->assert->assertTrue(true);
                } catch (ResponseValidationFailed $e) {
                    $this->assert->fail((string) $e);
                }
            }
        );
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

    /**
     * @param Request $request
     * @return OperationAddress[]
     * @throws ResponseValidationFailed
     */
    protected function findManyOperationAddressesOrFail(Request $request): array
    {
        $pathFinder = new PathFinder($this->validators->getSchema(), $request->getUri(), $request->getMethod());
        $leagueOperationAddresses = $pathFinder->search();

        if (count($leagueOperationAddresses) === 0) {
            throw new ResponseValidationFailed(
                sprintf(
                    "OpenAPI spec doesn't contain operations matching %s %s",
                    $request->getUri(),
                    $request->getMethod()
                )
            );
        }

        return array_map(
            fn (LeagueOperationAddress $leagueOperationAddress) => new OperationAddress(
                $leagueOperationAddress->path(),
                $leagueOperationAddress->method(),
            ),
            $leagueOperationAddresses
        );
    }
}
