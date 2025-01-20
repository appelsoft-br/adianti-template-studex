<?php
/**
 * SystemPermission
 *
 * @version    8.0
 * @package    model
 * @subpackage admin
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license-template
 */
class SystemPermission
{
    public static function checkPermission($action)
    {
        $ini = AdiantiApplicationConfig::get();
        
        $public_classes = !empty($ini['permission']['public_classes']) ? $ini['permission']['public_classes'] : [];
        $public_classes[] = 'LoginForm';
        
        $programs = TSession::getValue('programs');
        return (isset($programs[$action]) and $programs[$action]);
    }
}
