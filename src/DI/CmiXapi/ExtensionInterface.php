<?php

namespace ILIAS\Plugins\Events2Lrs\DI\CmiXapi;


interface ExtensionInterface
{

    public function request() : Request;

    public function statement() : Statement;

}