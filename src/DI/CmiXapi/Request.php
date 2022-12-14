<?php

namespace ILIAS\Plugins\Events2Lrs\DI\CmiXapi;

use ILIAS\Plugins\Events2Lrs\DI\CmiXapi\Request\Forward;
use ILIAS\Plugins\Events2Lrs\DI\Container;

class Request
{
    const XAPI_PSR7_REQUEST_HEADER = 'xAPI';

    /**
     * @var Container
     */
    protected $dic;
    /**
     * @var \ILIAS\DI\HTTPServices
     */
    private $http;

    public function __construct()
    {
        $this->dic = new Container();

        $this->http = $this->dic->http();

    }

    public function isXapiRequest() : bool
    {
        if($this->http->request()->hasHeader(self::XAPI_PSR7_REQUEST_HEADER)) {

            return true;

        }

        return false;

    }

    public function getXapiRequestHeaderLine() : string
    {

        return $this->isXapiRequest() ? $this->http->request()->getHeaderLine(self::XAPI_PSR7_REQUEST_HEADER) : '';

    }




    public function forward() : Forward
    {

        return new Forward();

    }

}