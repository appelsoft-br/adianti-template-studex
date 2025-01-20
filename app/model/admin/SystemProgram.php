<?php

use Adianti\Database\TRecord;

/**
 * SystemProgram
 *
 * @version    8.0
 * @package    model
 * @subpackage admin
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license-template
 */
class SystemProgram extends TRecord
{
    const TABLENAME = 'system_program';
    const PRIMARYKEY = 'id';
    const IDPOLICY =  'max'; // {max, serial}

    // use SystemChangeLogTrait;

    /**
     * Constructor method
     */
    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('name');
        parent::addAttribute('controller');
        parent::addAttribute('ajuda');
    }
}
