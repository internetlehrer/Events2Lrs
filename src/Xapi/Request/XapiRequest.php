<?php
namespace ILIAS\Plugin\Events2Lrs\Xapi\Request;

/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

use Exception;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions as HttpRequestOptions;
use GuzzleHttp\Psr7\ServerRequest as HttpRequest;
use GuzzleHttp\Psr7\Uri as HttpRequestUri;
use GuzzleHttp\Client as HttpClient;
use ilLogger;
use ilLoggerFactory;
use Psr\Http\Message\ResponseInterface;

/**
 * Class XapiRequest
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 */
class XapiRequest
{
    const CONNECT_TIMEOUT = 5;
    const TIMEOUT = 5;

	/**
	 * @var ilLogger
	 */
	protected $log;
	/**
	 * string
	 */
	protected $url;
	/**
	 * @var string
	 */
	protected $authKey;
	/**
	 * @var string
	 */
	protected $authSecret;
    /**
     * @var bool
     */
    protected $requestStatus = false;

    /**
	 * XapiRequest constructor.
	 * @param ilLogger $log
	 * @param string $url
	 * @param string $authKey
	 * @param string $authSecret
	 */
	public function __construct(string $url, string $authKey, string $authSecret)
	{
		$this->log = ilLoggerFactory::getRootLogger();

		$this->url = $url;

		$this->authKey = $authKey;

		$this->authSecret = $authSecret;
	}
	
	/**
	 * @return string
	 */
	protected function buildBasicAuth(): string
    {
		return 'Basic ' . base64_encode($this->authKey . ':' . $this->authSecret);
	}
	
	/**
	 * @return HttpRequestUri
	 */
	protected function buildUri(): HttpRequestUri
    {

        return new HttpRequestUri($this->url);

	}
	
	/**
	 * @return array
	 */
	protected function buildHeaders($contentLength): array
    {
		return [
			'Authorization' => [$this->buildBasicAuth()],
			'Content-Length' => [strlen($contentLength)],
			'Content-Type' => ['application/json'],
			'Accept' => ['*/*'],
			'X-Experience-API-Version' => ['1.0.3']
		];
	}


	/**
	 * @return array
	 */
	protected function buildRequestOptions(): array
    {
		return [
			HttpRequestOptions::VERIFY => !DEVMODE,
			HttpRequestOptions::CONNECT_TIMEOUT => self::CONNECT_TIMEOUT,
            HttpRequestOptions::TIMEOUT => self::TIMEOUT
		];
	}

    /**
     * @param string $statement
     * @return bool
     */
    public function sendStatement(string $statement) : bool
    {

        $request = new HttpRequest(
            'POST',
            $this->buildUri(),
            $this->buildHeaders($statement),
            $statement,
            '1.1',
            $_SERVER
        );

        try
        {
            $httpClient = new HttpClient();

            $asyncPromise = $httpClient->sendAsync($request, $this->buildRequestOptions())
                ->then(function(ResponseInterface $response) {

                    if($this->requestStatus = $response->getStatusCode() === 200) {

                        $this->log->log('SUCCESS: Status : '.$response->getStatusCode().' | Response: ' . $response->getBody());

                    } else {

                        $this->log->log('FAILED: Status : '.$response->getStatusCode().' | Response: ' . $response->getBody());

                    }
                });

            $asyncPromise->wait();

        } catch (Exception $e)
        {

            $this->log->error($e);

            return false;

        }

        return $this->requestStatus;

    }
}
