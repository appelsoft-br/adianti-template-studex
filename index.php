<?php

require_once 'init.php';
$server = $_SERVER['HTTP_HOST'];
if ($_SERVER['HTTP_HOST'] != 'localhost') {
    if ($_SERVER['HTTP_HOST'] != 'localhost' and !isset($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != "on") {
        header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"], true, 301);
        exit;
    }
}

$ini = AdiantiApplicationConfig::get();
$theme  = $ini['general']['theme'];
$class  = isset($_REQUEST['class']) ? $_REQUEST['class'] : '';
$public = in_array($class, !empty($ini['permission']['public_classes']) ? $ini['permission']['public_classes'] : []);

use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Core\AdiantiTemplateParser;
use Adianti\Registry\TSession;

$sessionHandler = new GerenciadorSessoes();
new TSession($sessionHandler);

ApplicationAuthenticationService::checkMultiSession();
ApplicationTranslator::setLanguage( TSession::getValue('user_language'), true );

if ( TSession::getValue('logged') )
{
    if (isset($_REQUEST['template']) AND $_REQUEST['template'] == 'iframe')
    {
        $content = file_get_contents("app/templates/{$theme}/iframe.html");
    }
    else
    {
        $ambiente = new AmbienteConexao();
        $arquivoMenu = 'menu.xml';
        
        switch (SessaoService::buscarTipoUsuario()) {
            case TipoUsuario::ADMIN_REGIONAL:
            case TipoUsuario::FUNCIONARIO_REGIONAL:
                $arquivoMenu = 'menu_regional.xml';
                $content = str_replace('{HOMEPAGE}', "HomeRegional", $content);
                break;
    
            case TipoUsuario::VENDEDOR:
                $arquivoMenu = 'menu_vendedor.xml';
                $content = str_replace('{HOMEPAGE}', "HomeVendedor", $content);
                break;
    
            default:
                $arquivoMenu = 'menu.xml';
                $content = str_replace('{HOMEPAGE}', "WelcomeView", $content);
                break;
        }

        $content = file_get_contents("app/templates/{$theme}/layout.html");
        $content = str_replace('{MENU}', AdiantiMenuBuilder::parse($arquivoMenu, $theme), $content);
        $content = str_replace('{MENUTOP}', AdiantiMenuBuilder::parseNavBar('menu-top.xml', $theme), $content);
        $content = str_replace('{MENUBOTTOM}', AdiantiMenuBuilder::parseNavBar('menu-bottom.xml', $theme), $content);
    }
}
else
{
    if (isset($ini['general']['public_view']) && $ini['general']['public_view'] == '1')
    {
        $content = file_get_contents("app/templates/{$theme}/public.html");
        $menu    = AdiantiMenuBuilder::parse('menu-public.xml', $theme);
        $content = str_replace('{MENU}', $menu, $content);
        $content = str_replace('{MENUTOP}', AdiantiMenuBuilder::parseNavBar('menu-top-public.xml', $theme), $content);
        $content = str_replace('{MENUBOTTOM}', AdiantiMenuBuilder::parseNavBar('menu-bottom-public.xml', $theme), $content);
    }
    else
    {
        $content = file_get_contents("app/templates/{$theme}/login.html");
    }
}

$content = ApplicationTranslator::translateTemplate($content);
$content = AdiantiTemplateParser::parse($content);

echo $content;
unset($_REQUEST['__tawkuuid']);
unset($_REQUEST['PHPSESSID']);
unset($_REQUEST['TawkConnectionTime']);
unset($_REQUEST['_ga']);
unset($_REQUEST['_gid']);
unset($_REQUEST['_gat_gtag_UA_132971656_1']);
unset($_REQUEST['PHPSESSID_sistema_studex_teste']);
unset($_REQUEST['PHPSESSID_sistema_studex']);
unset($_REQUEST['PHPSESSID_sistema_studex_novo']);
unset($_REQUEST['PHPSESSID_cursos_studex']);

if (isset($_REQUEST['class'])) {
    $method = isset($_REQUEST['method']) ? $_REQUEST['method'] : NULL;
    AdiantiCoreApplication::loadPage($_REQUEST['class'], $method, $_REQUEST);
} else {
    AdiantiCoreApplication::loadPage('LoginForm', '', $_REQUEST);
}
