<?php
namespace ILIAS\Plugins\Events2Lrs\DI;

/**
 * Extending global DIContainer
 *
 * @author Uwe Kohnle
 * @author Christian Stepper
 */

use ilGSProviderFactory;
use ILIAS\GlobalScreen\Services;
use ILIAS\Plugins\Events2Lrs\DI\Database as ilgDb;

use ILIAS\Plugins\Events2Lrs\DI\CmiXapi as ilgCmiXapi;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

class Container extends \ILIAS\DI\Container
{

    /*public function database() : ilgDb\ExtensionInterface
    {

        return $this['ilDB'];

    }*/


    public function xApi() : CmiXapi\Extension
    {

        return $GLOBALS['xApi']; # new ilgCmiXapi\Extension();

    }


    /**
     *
     * @param mixed $param
     * @return void
     */
    public function dex($param)
    {

        echo '<pre>';

        var_dump($param);

        exit;

    }


    public function __call($method, $args)
    {
        global $DIC; /** @var \ILIAS\DI\Container $DIC */

        if($DIC->offsetExists($method)) {

            return $DIC->$method();

        } else {

            return $this->$method();

        }
    }


    public function __construct()
    {

        global $DIC; /** @var \ILIAS\DI\Container $DIC */

        #$this->resetDb($DIC);

        $this->initXapi($DIC);

        $offset = [];

        foreach ($DIC->keys() as $key) {

            $offset[$key] = $DIC->raw($key);

        }

        parent::__construct($offset);

        $DIC = $this;

    }

    private function resetDb(\ILIAS\DI\Container $DIC)
    {
        $DIC->offsetUnset('ilDB');

        $GLOBALS['ilDB'] = new ilgDb\Extension();

        $DIC->offsetSet('ilDB', $GLOBALS['ilDB']);
    }


    private function initXapi(\ILIAS\DI\Container $DIC)
    {
        $GLOBALS['xApi'] = new ilgCmiXapi\Extension();

        $DIC->offsetSet('xApi', $GLOBALS['xApi']);

    }

}