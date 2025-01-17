<?php

use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use \Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ApplicationAuthenticationService
{
    /**
     * Authenticate user and load permissions
     */
    public static function authenticate($login, $password)
    {
        $ini  = AdiantiApplicationConfig::get();

        TTransaction::open('sample');
        $user = SystemUser::validate($login);

        if ($user) {
            if (!empty($ini['sample']['auth_service']) and class_exists($ini['sample']['auth_service'])) {
                $service = $ini['sample']['auth_service'];
                $service::authenticate($login, $password);
            } else {
                SystemUser::authenticate($login, $password);
            }

            self::loadSessionVars($user);

            // register REST profile
            // SystemAccessLog::registerLogin();

            return $user;
        }

        TTransaction::close();
    }

    /**
     * Set Unit when multi unit is turned on
     * @param $unit_id Unit id
     */
    public static function setUnit($unit_id)
    {
        $ini  = AdiantiApplicationConfig::get();

        if (!empty($ini['general']['multiunit']) and $ini['general']['multiunit'] == '1' and !empty($unit_id)) {
            TSession::setValue('userunitid',   $unit_id);
            TSession::setValue('userunitname', SystemUnit::findInTransaction('sample', $unit_id)->name);

            if (!empty($ini['general']['multi_database']) and $ini['general']['multi_database'] == '1') {
                TSession::setValue('unit_database', SystemUnit::findInTransaction('sample', $unit_id)->connection_name);
            }
        }
    }

    /**
     * Set language when multi language is turned on
     * @param $lang_id Language id
     */
    /* public static function setLang($lang_id)
    {
        $ini  = AdiantiApplicationConfig::get();

        if (!empty($ini['general']['multi_lang']) and $ini['general']['multi_lang'] == '1' and !empty($lang_id)) {
            TSession::setValue('user_language', $lang_id);
        }
    }*/

    /**
     * Load user session variables
     */
    public static function loadSessionVars($user)
    {
        $programs = $user->getPrograms();
        $programs['LoginForm'] = TRUE;

        TSession::setValue('logged', TRUE);
        SessaoService::salvarLoginUsuario($user->login);
        SessaoService::salvarIdUsuarioLogado($user->id);
        TSession::setValue('usergroupids', $user->getSystemUserGroupIds());
        TSession::setValue('userunitids', $user->getSystemUserUnitIds());
        TSession::setValue('username', $user->name);
        TSession::setValue('usermail', $user->email);
        TSession::setValue('frontpage', '');
        TSession::setValue('programs', $programs);

        if (!empty($user->unit)) {
            TSession::setValue('userunitid', $user->unit->id);
            TSession::setValue('userunitname', $user->unit->name);
        }
    }

    /**
     * Authenticate user from JWT token
     */
    public static function fromToken($token)
    {
        $ini = AdiantiApplicationConfig::get();
        $key = APPLICATION_NAME . $ini['general']['seed'];

        if (empty($ini['general']['seed'])) {
            throw new Exception('Application seed not defined');
        }

        $token = (array)  JWT::decode($token, new Key($key, 'HS256'));

        $login   = $token['user'];
        $userid  = $token['userid'];
        $name    = $token['username'];
        $email   = $token['usermail'];
        $expires = $token['expires'];

        if ($expires < strtotime('now')) {
            throw new Exception('Token expired. This operation is not allowed');
        }

        TSession::setValue('logged',   TRUE);
        SessaoService::salvarLoginUsuario($login);
        SessaoService::salvarIdUsuarioLogado($userid);
        TSession::setValue('username', $name);
        TSession::setValue('usermail', $email);
    }
}
