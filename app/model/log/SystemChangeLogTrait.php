<?php

use Adianti\Database\TTransaction;

/**
 * SystemChangeLogTrait
 *
 * @version    8.0
 * @package    model
 * @subpackage log
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license-template
 */
trait SystemChangeLogTrait
{
    public function onAfterDelete($object)
    {
        SystemChangeLog::register($this, $object, array());
    }

    public function onBeforeStore($object)
    {
        $pk = $this->getPrimaryKey();
        $this->lastState = array();
        if (isset($object->$pk) and self::exists($object->$pk)) {
            $this->lastState = parent::load($object->$pk)->toArray();
        }
    }

    public function onAfterStore($object)
    {
        SystemChangeLog::register($this, $this->lastState, (array) $object);
    }
}
