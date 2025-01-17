<?php

use Adianti\Database\TRecord;

/**
 * System_user_group Active Record
 * @author  <your-name-here>
 */
class SystemUserGroup extends TRecord
{

    const GRUPO_ADMIN = 1;
    const GRUPO_FDV_REGIONAL = 47;
    const GRUPO_ESTOQUE_FUNC_REGIONAL = 41;

    const TABLENAME = 'system_user_group';
    const PRIMARYKEY = 'id';
    const IDPOLICY =  'max'; // {max, serial}

    private $system_group;
    /**
     * Constructor method
     */
    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('system_user_id');
        parent::addAttribute('system_group_id');
    }
    public function add($system_user_id, $system_group_id)
    {
        $obj = new SystemUserGroup();
        $obj->system_user_id = $system_user_id;
        $obj->system_group_id = $system_group_id;
        $obj->store();
    }
    public function get_system_group()
    {

        // loads the associated object
        if (empty($this->system_group))
            $this->system_group = new SystemGroup($this->system_group_id);

        // returns the associated object
        return $this->system_group;
    }
}
