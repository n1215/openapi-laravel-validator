<?php

declare(strict_types=1);

namespace N1215\OpenApiValidation\Laravel\Middleware;

use Closure;
use Illuminate\Contracts\Routing\ResponseFactory;
use League\OpenAPIValidation\PSR7\PathFinder;
use N1215\OpenApiValidation\HttpFoundation\Validators;
use N1215\OpenApiValidation\Laravel\ValidatorsFactoryInterface;
use N1215\OpenApiValidation\OperationAddress;
use N1215\OpenApiValidation\RequestValidationFailed;
use N1215\OpenApiValidation\ResponseValidationFailed;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateWithOpenApi
{
    protected string $basePath = '/';

    protected Validators $validators;

    public function __construct(
        ValidatorsFactoryInterface $validatorsFactory,
        protected readonly ResponseFactory $responseFactory
    ) {
        $this->validators = $validatorsFactory->make();
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle($request, Closure $next): Response
    {
        try {
            $this->validators->getRequestValidator()->validate($request);
        } catch (RequestValidationFailed $e) {
            return $this->makeRequestValidationFailedResponse($e);
        }

        $response = $next($request);
        assert($response instanceof Response);

        $pathFinder = new PathFinder($this->validators->getSchema(), $request->getUri(), $request->getMethod());
        $operationAddresses = $pathFinder->search();

        if (count($operationAddresses) === 0) {
            return $this->makeResponseValidationFailedResponse(
                new ResponseValidationFailed(
                    sprintf(
                        "OpenAPI spec doesn't contain operations matching %s %s",
                        $request->getUri(),
                        $request->getMethod()
                    )
                )
            );
        }

        try {
            $this->validators->getResponseValidator()->validate(
                new OperationAddress(
                    $operationAddresses[0]->path(),
                    $operationAddresses[0]->method(),
                ),
                $response
            );
        } catch (ResponseValidationFailed $e) {
            return $this->makeResponseValidationFailedResponse($e);
        }

        return $response;
    }

    protected function makeRequestValidationFailedResponse(RequestValidationFailed $e): Response
    {
        return $this->responseFactory->json(
            [
                'message' =>  $e->getMessage(),
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    protected function makeResponseValidationFailedResponse(ResponseValidationFailed $e): Response
    {
        return $this->responseFactory->json(
            [
                'message' =>  $e->getMessage(),
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
