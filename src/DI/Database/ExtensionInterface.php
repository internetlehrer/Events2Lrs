<?php

namespace ILIAS\Plugins\Events2Lrs\DI\Database;

use ilDBInterface;
use ilLogger;

interface ExtensionInterface extends ilDBInterface
{

    public function select(?string $select = null) : Select;

    public function delete(?string $delete = null, ?string $alias = null) : Delete;

    public function withLogger(?ilLogger $logger = null);

}