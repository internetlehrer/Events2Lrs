<?php

namespace ILIAS\Plugins\Events2Lrs\DI\CmiXapi\Request;

use Exception;
use ilCmiXapiLrsType;
use ILIAS\Plugin\Events2Lrs\Xapi\Request\XapiRequest;
use ilLoggerFactory;

class Forward
{

    /**
     * @var XapiRequest|null $xapiRequest
     */
    protected $xapiRequest = null;

    /**
     * @var string|null $xapiStatement
     */
    protected $xapiStatement = null;

    /**
     * @var string|null $basicAuth
     */
    protected $basicAuth;

    /**
     * @var string|null $basicAuth
     */
    protected $basicAuthUser;

    /**
     * @var string|null $basicAuth
     */
    protected $basicAuthSecret;

    /**
     * @var string|null $basicAuth
     */
    protected $lrsEndpointUrl;

    /**
     * @var ilCmiXapiLrsType
     */
    protected $lrsType;

    public function withBasicAuth(string $basicAuth): self
    {

        $clone = clone($this);

        $clone->basicAuth = $basicAuth;

        return $clone;

    }

    public function withBasicAuthUser(string $basicAuthUser): self
    {

        $clone = clone($this);

        $clone->basicAuthUser = $basicAuthUser;

        return $clone;

    }

    public function withBasicAuthSecret(string $basicAuthSecret): self
    {

        $clone = clone($this);

        $clone->basicAuthSecret = $basicAuthSecret;

        return $clone;

    }

    public function withLrsEndpointUrl(string $url) : self
    {
        $clone = clone($this);

        $clone->lrsEndpointUrl = $url;

        return $clone;
    }

    public function withLrsType(ilCmiXapiLrsType $lrsType) : self
    {
        $clone = clone($this);

        $clone->lrsType = $lrsType;

        return $clone;
    }

    public function withStatement(string $xapiStatement) : self
    {
        $clone = clone($this);

        $clone->xapiStatement = $xapiStatement;

        return $clone;
    }

    /**
     * @throws Exception
     */
    public function send() : string
    {
        $clone = clone($this);

        $clone->xapiRequest = new XapiRequest(
            $clone->lrsType->getLrsEndpointStatementsLink(),
            $clone->lrsType->getLrsKey(),
            $clone->lrsType->getLrsSecret()
        );

        return $clone->xapiRequest->sendStatement(
            $clone->xapiStatement
        );
    }

}