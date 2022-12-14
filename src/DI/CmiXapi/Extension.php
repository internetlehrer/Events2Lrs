<?php

namespace ILIAS\Plugins\Events2Lrs\DI\CmiXapi;


class Extension implements ExtensionInterface
{
    public function request(): Request
    {

        return new Request();

    }


    public function statement(): Statement
    {

        return new Statement();

    }

}