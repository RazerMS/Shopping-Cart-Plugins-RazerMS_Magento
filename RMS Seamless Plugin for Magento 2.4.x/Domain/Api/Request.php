<?php

namespace RazerPay\Payment\Domain\Api;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Message;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class Request
{
    protected \RazerPay\Payment\Gateway\Config\Config $paymentGatewayConfig;

    protected \RazerPay\Payment\Logger\Api $paymentApiLogger;

    protected string $method = \Magento\Framework\App\Request\Http::METHOD_GET;

    protected string $productionEndpoint;
    protected string $sandboxEndpoint;

    protected array $bodyParams;

    public function __construct(
        \RazerPay\Payment\Gateway\Config\Config $paymentGatewayConfig,
        \RazerPay\Payment\Logger\Api $paymentApiLogger,
        array $bodyParams = []
    ) {
        $this->paymentGatewayConfig = $paymentGatewayConfig;
        $this->paymentApiLogger = $paymentApiLogger;
        $this->bodyParams = $bodyParams;
    }

    protected function method(): string
    {
        if ($this->method) {
            return $this->method;
        }

        throw new \UnexpectedValueException('Cannot resolve method.');
    }

    protected function endpoint(): string
    {
        if ($this->paymentGatewayConfig->isProduction()) {
            return $this->getProductionEndpoint();
        }

        if ($this->paymentGatewayConfig->isSandbox()) {
            return $this->getSandboxEndpoint();
        }

        throw new \UnexpectedValueException('Cannot resolve endpoint.');
    }

    protected function getProductionEndpoint(): string
    {
        if ($this->productionEndpoint) {
            return $this->productionEndpoint;
        }

        throw new \UnexpectedValueException('[Request.productionEndpoint] property is undefined.');
    }

    protected function getSandboxEndpoint(): string
    {
        if ($this->sandboxEndpoint) {
            return $this->sandboxEndpoint;
        }

        throw new \UnexpectedValueException('[Request.sandboxEndpoint] property is undefined.');
    }

    protected function headers(): array
    {
        return [];
    }

    protected function body(): ?\Psr\Http\Message\StreamInterface
    {
        return null;
    }

    protected function client(): \GuzzleHttp\Client
    {
        $handlerStack = HandlerStack::create();
        $handlerStack->push($this->addLogMiddleware());

        return new \GuzzleHttp\Client([
            'handler' => $handlerStack,
        ]);
    }

    public function send()
    {
        $response = $this->client()->send(
            new \GuzzleHttp\Psr7\Request($this->method(), $this->endpoint(), $this->headers(), $this->body()),
        );

        try {
            $responseBody = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $responseBody = $response->getBody();
        }

        return $responseBody;
    }

    protected function addLogMiddleware(): callable
    {
        return function (callable $handler): callable {
            return function (
                RequestInterface $request,
                array $options = []
            ) use ($handler) {
                return $handler($request, $options)->then(
                    function (ResponseInterface $response) use ($request): ResponseInterface {
                        $this->paymentApiLogger->info(
                            (string)$request->getUri(),
                            [
                                'request' => Message::toString($request),
                                'response' => Message::toString($response),
                            ]
                        );

                        return $response;
                    },
                    function ($reason) use ($request): PromiseInterface {
                        $response = $reason instanceof \GuzzleHttp\Exception\RequestException ? $reason->getResponse() : null;

                        $this->paymentApiLogger->info(
                            (string)$request->getUri(),
                            [
                                'request' => Message::toString($request),
                                'response' => $response ? Message::toString($response) : null,
                            ]
                        );

                        return \GuzzleHttp\Promise\Create::rejectionFor($reason);
                    }
                );
            };
        };
    }
}
