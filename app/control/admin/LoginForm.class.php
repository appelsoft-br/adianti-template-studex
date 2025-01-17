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
 * LoginForm Registration
 * @author  <your name here>
 */
class LoginForm extends TPage
{
    protected $form; // form

    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct($param)
    {
        parent::__construct();
        $table = new TTable;
        $table->width = '100%';
        // creates the form
        $this->form = new TForm('form_login');
        $this->form->class = 'login-form';

        // add the notebook inside the form
        $this->form->add($table);

        // create the form fields
        $login = new TEntry('login');
        $login->placeholder = _t('User');
        $login->addValidation(_t('User'), new TRequiredValidator);

        $password = new TPassword('password');
        $password->addValidation(_t('Password'), new TRequiredValidator);
        $password->placeholder = _t('Password');

        // define the sizes
        $login->setSize('100%', 40);
        $password->setSize('100%', 40);

        $row = $table->addRow();


        $this->container0 = new TElement('div');

        $container1 = new TElement('div');
        $container1->add($login);

        $container2 = new TElement('div');
        $container2->add($password);

        //$login->addValidation('Login',new TCharValidator);

        $row = $table->addRow();
        $row->addCell($this->container0)->colspan = 2;

        $row = $table->addRow();
        $row->addCell($container1)->colspan = 2;

        // add a row for the field password
        $row = $table->addRow();
        $row->addCell($container2)->colspan = 2;

        // create an action button (save)
        $save_button = new TButton('save');
        $save_button->class = 'botao-login';
        // define the button action
        $save_button->setAction(new TAction(array($this, 'onLogin')), _t('Log in'));

        $row = $table->addRow();
        $cell = $row->addCell($save_button);
        $cell->colspan = 2;
        $cell->style = 'text-align:center';


        $this->form->setFields(array($login, $password, $save_button));

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

            $param;
            TTransaction::open('sample');
            $user = Usuario::authenticate($data->login, $data->password);
            if ($user) {
                SystemAccessLog::registerLogin();
                TSession::regenerate();
                $programs = $user->getPrograms();
                $programs['LoginForm'] = TRUE;
                $groups = explode(',', $user->getSystemUserGroupIds());
                TSession::setValue('logged', TRUE);
                SessaoService::salvarLoginUsuario($data->login);
                SessaoService::salvarIdUsuarioLogado($user->id);
                TSession::setValue('usergroupids', $user->getSystemUserGroupIds());
                TSession::setValue('groups', $groups);
                TSession::setValue('username', $user->name);
                TSession::setValue('frontpage', '');
                TSession::setValue('programs', $programs);
                SessaoService::salvarTipoUsuario($user->tipo);

                if (!empty($user->unit)) {
                    TSession::setValue('userunitid', $user->unit->id);
                }

                switch ($user->tipo) {
                    case TipoUsuario::FUNCIONARIO_REGIONAL:
                        $cliente_id = Database::buscarPrimeiro("Select clientesId from clientes_usuarios where login = '{$data->login}'");
                        SessaoService::salvarIdRegional($cliente_id);
                        TSession::setValue('username', $user->nome);
                        $paginaRedirecionar = 'HomeRegional';
                        TSession::setValue('frontpage', 'HomeRegional');
                        break;
                    case TipoUsuario::ADMIN_REGIONAL:
                        $cliente_id = Database::buscarPrimeiro("Select id from clientes where login = '{$data->login}'");
                        $dados_usuario = Database::executa("Select * from clientes where login = '{$data->login}'");
                        SessaoService::salvarIdRegional($cliente_id);
                        TSession::setValue('username', $dados_usuario[0]['nome']);
                        $paginaRedirecionar = 'HomeRegional';
                        TSession::setValue('frontpage', 'HomeRegional');
                        break;
                    case TipoUsuario::ADMIN:
                        TSession::setValue('frontpage', 'WelcomeView');
                        $paginaRedirecionar = 'WelcomeView';
                        break;
                    case TipoUsuario::FUNCIONARIO_MATRIZ:
                        TSession::setValue('frontpage', 'WelcomeView');
                        $vendedor_id = Database::buscarPrimeiro("Select vendedor_id from funcionarios where login = '{$data->login}'");
                        SessaoService::salvarIdVendedor($vendedor_id);
                        if (TSession::getValue('usergroupids') == '28,2') {
                            $paginaRedirecionar = 'EntradaVendaList';
                        } else {
                            $paginaRedirecionar = 'WelcomeView';
                        }
                        break;
                    case TipoUsuario::VENDEDOR:
                        $vendedor_id = Database::buscarPrimeiro("Select id from pgto_vendedores where usuario_id = '{$user->id}'");
                        SessaoService::salvarIdVendedor($vendedor_id);
                        $regional_id = Database::buscarPrimeiro("select clientesId from pgto_vendedores where login ='{$user->id}'");
                        SessaoService::salvarIdRegional($regional_id);
                        TSession::setValue('frontpage', 'HomeVendedor');
                        $paginaRedirecionar = 'HomeVendedor';
                        break;
                    default:
                        $paginaRedirecionar = 'SistemaAntigo';
                        TSession::setValue('frontpage', 'SistemaAntigo');
                        break;
                }
            }

            self::mostrarAtualizacoes($user, $paginaRedirecionar);

            TTransaction::close();
        } catch (Exception $e) {
            $alert = new TElement('div');
            $alert->class = "alert alert-danger";
            $alert->add($e->getMessage());
            $this->container0->add($alert);
            TSession::setValue('logged', FALSE);
            TTransaction::rollback();
        }
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

    /** 
     * Reload permissions
     */
    public static function reloadPermissions()
    {
        try {
            TTransaction::open('sample');
            $user = SystemUser::newFromLogin(SessaoService::buscarLoginUsuario());
            if ($user) {
                $programs = $user->getPrograms();
                $programs['LoginForm'] = TRUE;
                TSession::setValue('programs', $programs);

                $frontpage = $user->frontpage;
                if ($frontpage instanceof SystemProgram and $frontpage->controller) {
                    TApplication::gotoPage($frontpage->controller); // reload
                } else {
                    TApplication::gotoPage('EmptyPage'); // reload
                }
            }
            TTransaction::close();
        } catch (Exception $e) {
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
}
