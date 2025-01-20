<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TTable;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TQuestion;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TPassword;
use Axdron\Radianti\Services\RadiantiTransaction;

/**
 * LoginForm
 *
 * @version    8.0
 * @package    control
 * @subpackage admin
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license-template
 */
class LoginForm extends TPage
{
    protected $form; // form

    /**
     * Class constructor
     * Creates the page and the registration form
     */
    public function __construct($param)
    {
        parent::__construct();
        
        $ini  = AdiantiApplicationConfig::get();
        
        // creates the form
        $this->form = new TModalForm('form_login');
        $this->form->setFormTitle('Login');
        
        if (!empty($ini['login']['logo']))
        {
            $logo = new TImage($ini['login']['logo']);
            $logo->style = 'margin:auto;max-width:100%';
            $this->form->setFormTitle($logo);
        }
        
        // create the form fields
        $login               = new TEntry('login');
        $password            = new TPassword('password');
        $previous_class      = new THidden('previous_class');
        $previous_method     = new THidden('previous_method');
        $previous_parameters = new THidden('previous_parameters');
        
        $login->disableAutoComplete();
        $password->disableAutoComplete();
        $login->setSize('100%');
        $password->setSize('100%');
        $login->placeholder = _t('User');
        $password->placeholder = _t('Password');
        $password->disableToggleVisibility();
        $login->autofocus = 'autofocus';
        
        $this->form->addRowField(_t('Login'), $login, true );
        $this->form->addRowField(_t('Password'), $password, true );
        
        if (!empty($ini['general']['multiunit']) and $ini['general']['multiunit'] == '1')
        {
            $unit_id = new TCombo('unit_id');
            $unit_id->setSize('100%');
            $login->setExitAction(new TAction( [$this, 'onExitUser'] ) );
            
            $this->form->addRowField(_t('Unit'), $unit_id, true );
        }
        
        if (!empty($ini['general']['multi_lang']) and $ini['general']['multi_lang'] == '1')
        {
            $lang_id = new TCombo('lang_id');
            $lang_id->setSize('100%');
            $lang_id->addItems( $ini['general']['lang_options'] );
            $lang_id->setValue( $ini['general']['language'] );
            $lang_id->setDefaultOption(FALSE);
            
            $this->form->addRowField(_t('Language'), $lang_id, true );
        }
        
        if (!empty($ini['recaptcha']) && $ini['recaptcha']['enabled'] == '1')
        {
            $recaptcha_html = str_replace('{sitekey}',$ini['recaptcha']['key'],file_get_contents('app/resources/recaptcha.html'));
            $this->form->addRowContent( $recaptcha_html );
        }
        
        if (!empty($param['previous_class']) && $param['previous_class'] !== 'LoginForm')
        {
            $previous_class->setValue($param['previous_class']);
            
            if (!empty($param['previous_method']))
            {
                $previous_method->setValue($param['previous_method']);
            }
            
            $previous_parameters->setValue(serialize($param));
        }
        
        $this->form->addAction(_t('Log in'), new TAction([$this, 'onLogin']), '' );
        
        if (isset($ini['permission']['user_register']) && $ini['permission']['user_register'] == '1')
        {
            $this->form->addFooterAction(_t('Create account'), new TAction(['SystemRegistrationForm', 'onLoad']), '');
        }
        
        if (isset($ini['permission']['reset_password']) && $ini['permission']['reset_password'] == '1')
        {
            $this->form->addFooterAction(_t('Reset password'), new TAction(['SystemRequestPasswordResetForm', 'onLoad']), '');
        }
        
        // add the form to the page
        parent::add($this->form);
    }

    /**
     * Authenticate the User
     */
    public function onLogin($param)
    {
        try {
            if (empty($param['login'])) {
                $data = $this->form->getData('StdClass');
                $this->form->validate();
            } else {
                $data = (object) $param;
            }
            
            if (!empty($ini['general']['require_terms']) && $ini['general']['require_terms'] == '1' && !empty($param['usage_term_policy']) AND empty($data->accept))
            {
                throw new Exception(_t('You need read and agree to the terms of use and privacy policy'));
            }
            
            $user = ApplicationAuthenticationService::authenticate( $data->login, $data->password, false );
            
            if ($user)
            {
                self::preCheckRecaptcha();
                
                if ( ($form = self::policyTermsVerification($user, $param)) instanceof BootstrapFormBuilder)
                {
                    new TInputDialog(_t('Terms of use and privacy policy'), $form);
                    return;
                }
                
                if ( ($form = self::checkTwoFactor($user, $param)) instanceof BootstrapFormBuilder)
                {
                    new TInputDialog(_t('Two factor authentication'), $form);
                    return;
                }
                
                self::checkRecaptcha();
                
                if (self::checkForPasswordRenew($user))
                {
                    AdiantiCoreApplication::gotoPage('SystemPasswordRenewalForm');
                    return;
                }
                
                ApplicationAuthenticationService::loadSessionVars($user, true);
                ApplicationAuthenticationService::setUnit( $data->unit_id ?? null );
                ApplicationAuthenticationService::setLang( $data->lang_id ?? null );
                SystemAccessLogService::registerLogin();
                SystemAccessNotificationLogService::registerLogin();
                
                $frontpage = $user->frontpage;
                if (!empty($param['previous_class']) && $param['previous_class'] !== 'LoginForm')
                {
                    AdiantiCoreApplication::gotoPage($param['previous_class'], $param['previous_method'], unserialize($param['previous_parameters'])); // reload
                }
                else if ($frontpage instanceof SystemProgram and $frontpage->controller)
                {
                    AdiantiCoreApplication::gotoPage($frontpage->controller); // reload
                    TSession::setValue('frontpage', $frontpage->controller);
                }
                else
                {
                    AdiantiCoreApplication::gotoPage('EmptyPage'); // reload
                    TSession::setValue('frontpage', 'EmptyPage');
                }

                self::mostrarAtualizacoes($user, $paginaRedirecionar);

            }
        }
        catch (Exception $e)
        {
            TSession::freeSession();
            self::resetRecaptcha();
            
            new TMessage('error',$e->getMessage());
            sleep(2);
            TTransaction::rollback();
        }
    }
    
    /**
     * Check if password needs to be renewed
     */
    private static function checkForPasswordRenew($user)
    {
        TTransaction::open('permission');
        if (SystemUserOldPassword::needRenewal($user->id))
        {
            TSession::setValue('login', $user->login);
            TSession::setValue('userid', $user->id);
            TSession::setValue('need_renewal_password', true);
            
            return true;
        }
        TTransaction::close();
    }
    
    /**
     * Check 2FA
     */
    private static function checkTwoFactor($user, $param)
    {
        if (!empty($user->otp_secret))
        {
            if (!empty($param['two_factor']))
            {
                $otp = \OTPHP\TOTP::create($user->otp_secret);
                if ($otp->verify($param['two_factor']))
                {
                    return true;
                }
            }
            
            $action = new TAction(['LoginForm', 'onLogin'], $param);
            $form = new BootstrapFormBuilder('two_factor_form');
            
            $two_factor = new TPassword('two_factor');
            $two_factor->style = 'height: 40px;';
            $two_factor->placeholder = _t('Authentication code');
            
            $form->addContent( [ new TLabel(_t('Enter the 6-digit code from your authenticator app')) ] );
            $form->addFields([$two_factor]);
            $form->addFields([new TEntry('lock_enter')])->style = 'display:none';;
            
            $btn = $form->addAction( _t('Authenticate'), $action, '');
            $btn->class = 'btn btn-primary';
            $btn->style = 'height: 40px;width: 90%;display: block;margin: auto;font-size: 17px;';
            
            return $form;
        }
    }

    private static function policyTermsVerification($user, $param)
    {
        $ini  = AdiantiApplicationConfig::get();
        
        $term_policy = SystemPreference::findInTransaction('permission', 'term_policy');
        
        if (!empty($ini['general']['require_terms']) && $ini['general']['require_terms'] == '1')
        {
            if ($user->accepted_term_policy !== 'Y' && !empty($term_policy) && empty($param['accept']))
            {
                $param['usage_term_policy'] = 'Y';
                $action = new TAction(['LoginForm', 'onLogin'], $param);
                $form = new BootstrapFormBuilder('term_policy');
    
                $content = new TElement('div');
                $content->style = "max-height: 45vh; overflow: auto; margin-bottom: 10px;";
                $content->add($term_policy->value);
    
                $check = new TCheckGroup('accept');
                $check->addItems(['Y' => _t('I have read and agree to the terms of use and privacy policy')]);
    
                $form->addContent([$content]);
                $form->addFields([$check]);
                $btn = $form->addAction( _t('Accept'), $action, 'fas:check');
                $btn->class = 'btn btn-primary';
                return $form;
            }
            
            if ($user->accepted_term_policy !== 'Y' && !empty($term_policy) && !empty($param['accept']))
            {
                TTransaction::open('permission');
                $user->accepted_term_policy = 'Y';
                $user->accepted_term_policy_at = date('Y-m-d H:i:s');
                $user->accepted_term_policy_data = json_encode($_SERVER);
                $user->store();
                TTransaction::close();
            }
        }
        AdiantiCoreApplication::gotoPage($param['paginaDestino']);
    }
    
    /**
     * Pre validate recaptcha
     */
    private static function preCheckRecaptcha()
    {
        $ini  = AdiantiApplicationConfig::get();
        
        if (!empty($ini['recaptcha']) && $ini['recaptcha']['enabled'] == '1')
        {
            if (empty($_REQUEST["g-recaptcha-response"]))
            {
                throw new Exception(_t('Invalid captcha'));
            }
        }
    }
    
    /**
     * Check Recaptcha
     */
    private static function checkRecaptcha()
    {
        $ini  = AdiantiApplicationConfig::get();
        
        if (!empty($ini['recaptcha']) && $ini['recaptcha']['enabled'] == '1')
        {
            RecaptchaServices::validate();
        }
    }
    
    /**
     * Reset Recaptcha
     */
    private static function resetRecaptcha()
    {
        $ini  = AdiantiApplicationConfig::get();
        
        if (!empty($ini['recaptcha']) && $ini['recaptcha']['enabled'] == '1')
        {
            RecaptchaServices::reset();
        }
    }
    
    /** 
     * Reload permissions
     */
    public static function reloadPermissions()
    {
        try
        {
            SystemPermissionService::reloadPermissions();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * Logout
     */
    public static function onLogout()
    {
        SystemAccessLog::registerLogout();
        TSession::freeSession();
        AdiantiCoreApplication::gotoPage('LoginForm', '');
    }
  
    static function mostrarAtualizacoes(Usuario $usuario, string $paginaDestino)
    {
        $atualizacoes = $usuario->buscarAtualizacoesNaoVisualizadas();

        $idUltimaAtualizacao = null;
        $mensagem = '';

        foreach ($atualizacoes as $atualizacao) {
            if ($idUltimaAtualizacao == null || $atualizacao->id > $idUltimaAtualizacao)
                $idUltimaAtualizacao = $atualizacao->id;
            $mensagem .= TDate::date2br($atualizacao->data_atualizacao) . ' - ' . $atualizacao->area . ' - ' . $atualizacao->descricao . '<br><br>';
        }

        if (!empty($mensagem)) {
            $acaoVerNovamente = new TAction(array(self::class, 'direcionarPagina'), ['paginaDestino' => $paginaDestino]);
            $acaoOk = new TAction(array(self::class, 'direcionarPagina'), ['paginaDestino' => $paginaDestino, 'idUltimaAtualizacao' => $idUltimaAtualizacao]);
            new TQuestion(title_msg: 'Atualizações', message: $mensagem, label_yes: 'Ok, estou ciente', action_yes: $acaoOk, label_no: 'Ver novamente no próximo login', action_no: $acaoVerNovamente);
        } else {
            AdiantiCoreApplication::gotoPage($paginaDestino);
        }
    }

    static function direcionarPagina($param)
    {
        if (isset($param['idUltimaAtualizacao'])) {
            RadiantiTransaction::salvar(function () use ($param) {
                $usuario = Usuario::find(SessaoService::buscarIdUsuarioLogado());
                $usuario->ultima_atualizacao_vista_id = $param['idUltimaAtualizacao'];
                unset($usuario->senha);
                $usuario->store();
            });
        }
        AdiantiCoreApplication::gotoPage($param['paginaDestino']);
    }

}
