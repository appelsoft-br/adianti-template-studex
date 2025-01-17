<?php

use Adianti\Control\TAction;
use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Registry\TSession;
use Adianti\Widget\Dialog\TMessage;

require_once 'init.php';

class TApplication extends AdiantiCoreApplication
{
    public static function run($debug = null)
    {

        $sessionHandler = new GerenciadorSessoes();
        new TSession($sessionHandler);

        if ($_REQUEST) {
            $ini    = AdiantiApplicationConfig::get();
            $debug  = is_null($debug) ? $ini['general']['debug'] : $debug;
            $class  = isset($_REQUEST['class']) ? $_REQUEST['class'] : '';
            $public = in_array($class, $ini['permission']['public_classes']);

            if (TSession::getValue('logged')) // logged
            {
                $programs = (array) TSession::getValue('programs'); // programs with permission
                $programs = array_merge($programs, self::getDefaultPermissions());

                if (isset($programs[$class]) or $public) {
                    parent::run($debug);
                } else {
                    new TMessage('error', _t('Permission denied'));
                }
            } else if ($class == 'LoginForm' or $public) {
                parent::run($debug);
            } else {
                new TMessage('error', _t('Permission denied'), new TAction(array(LoginForm::class, 'onLogout')));
            }
        }
    }

    public static function getDefaultPermissions()
    {
        return [
            'Adianti\Base\TStandardSeek' => TRUE,
            'LoginForm' => TRUE,
            'AdiantiMultiSearchService' => TRUE,
            'AdiantiUploaderService' => TRUE,
            'AdiantiAutocompleteService' => TRUE,
            'SystemDocumentUploaderService' => TRUE,
            'EmptyPage' => TRUE,
            'MessageList' => TRUE,
            'NotificationList' => TRUE,
            'SearchBox' => TRUE,
            'PfxToBase64Service' => TRUE,
            'UploaderService' => TRUE,
            'SearchInputBox' => TRUE
        ];
    }
}

TApplication::run();
