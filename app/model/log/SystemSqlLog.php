<?php

use Adianti\Database\TRecord;
use Adianti\Database\TTransaction;
use Adianti\Log\AdiantiLoggerInterface;

/**
 * SystemSqlLog
 *
 * @version    8.0
 * @package    model
 * @subpackage log
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license-template
 */
class SystemSqlLog extends TRecord implements AdiantiLoggerInterface
{
    const TABLENAME = 'system_sql_log';
    const PRIMARYKEY = 'id';
    const IDPOLICY =  'max'; // {max, serial}

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('logdate');
        parent::addAttribute('login');
        parent::addAttribute('database_name');
        parent::addAttribute('sql_command');
        parent::addAttribute('statement_type');
    }

    /**
     * Writes an message in the global logger
     * @param  $message Message to be written
     */
    public function write($message)
    {
        $dbname = TTransaction::getDatabase();

        // avoid log of log
        /* comentei log
        if ($dbname !== 'log' AND (in_array(substr($message,0,6), array('INSERT', 'UPDATE', 'DELETE') ) ) )
        {
            $time = date("Y-m-d H:i:s");
            
            TTransaction::open('log');
            $object = new self;
            $object->logdate = $time;
            $object->login = SessaoService::buscarLoginUsuario();
            $object->database_name = $dbname;
            $object->sql_command = $message;
            $object->statement_type = strtoupper(substr($message,0,6));
            $object->store();
            TTransaction::close();
        }
        */
    }
}
