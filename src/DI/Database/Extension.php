<?php
namespace ILIAS\Plugins\Events2Lrs\DI\Database;


use Exception;

use ilDatabaseException;

use ilException;

use ILIAS\DI\Container;

use ilDBInterface;

use ilDBPdo;

use ilLogger;

use ilPDOStatement;


class Extension extends ilDBPdo implements ExtensionInterface
{

    /**
     * @var ilDBInterface
     */
    private $parentInstance;

    /**
     * @var ilLogger|null
     */
    private $logger;


    /**
     * @param string|null $select
     * @return Select
     */
    public function select(?string $select = null): Select
    {

        return new Select($select);

    }


    /**
     * @param string|null $delete
     * @param string|null $alias
     * @return Delete
     */
    public function delete(?string $delete = null, ?string $alias = null): Delete
    {

        return new Delete($delete, $alias);

    }


    /**
     * @param string|QueryBuilder $query
     * @return ilPDOStatement
     * @throws ilException
     */
    public function query($query) : ilPDOStatement
    {

        $query = $query instanceof QueryBuilder ? $query->getSQL() : $query;

        return $this->handleLog($query)->parentInstance->query($query);

    }


    /**
     * @param string $query
     * @return int|void
     * @throws ilException
     */
    public function manipulate($query)
    {

        $query = $query instanceof QueryBuilder ? $query->getSQL() : $query;

        return $this->handleLog($query)->parentInstance->manipulate($query);

    }

    /**
     * call with $logger = null disables logging.
     * @param ilLogger|null $logger
     * @return void
     */
    public function withLogger(?ilLogger $logger = null) : self
    {
        $this->logger = $logger;

        return $this;
    }

    private function handleLog(string $message) : self
    {
        if($this->logger ?? false) {

            $this->logger->log($message);

        }

        return $this;
    }


    public function initHelpers()
    {

        return $this->parentInstance->initHelpers();

    }

    /**
     * @param string $table_name
     * @return int
     */
    public function nextId($table_name): int
    {

        return $this->parentInstance->nextId($table_name);

    }


    /**
     * @throws ilDatabaseException
     * @throws Exception
     */
    public function __construct()
    {
        global $DIC; /** @var Container $DIC */

        $this->dic = $DIC;

        $ilDB = \ilDBWrapperFactory::getWrapper(IL_DB_TYPE);

        $ilDB->initFromIniFile($GLOBALS["DIC"]["ilClientIniFile"]);

        $ilDB->connect();


        $this->parentInstance = $ilDB;

        $this->manager = $ilDB->manager;

        $this->reverse = $ilDB->reverse;

        $this->field_definition = $ilDB->field_definition;

        $this->pdo = $ilDB->pdo;

    }


    public function __call($fn, $args)
    {
        if(in_array($fn, get_class_methods($this->parentInstance))) {

            $this->parentInstance->$fn(implode(',', $args));

        }
    }


}