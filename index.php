<?php

require_once 'init.php';

use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Core\AdiantiTemplateParser;
use Adianti\Registry\TSession;

$server = $_SERVER['HTTP_HOST'];
if ($_SERVER['HTTP_HOST'] != 'localhost') {
    if ($_SERVER['HTTP_HOST'] != 'localhost' and !isset($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != "on") {
        header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"], true, 301);
        exit;
    }
}

$sessionHandler = new GerenciadorSessoes();
new TSession($sessionHandler);


$theme  = $ini['general']['theme'];
$content     = file_get_contents("app/templates/{$theme}/layout.html");
//$menu_string = AdiantiMenuBuilder::parse('menu.xml', $theme);
//$content     = str_replace('{MENU}', $menu_string, $content);
//$content     = ApplicationTranslator::translateTemplate($content);
$content     = str_replace('{LIBRARIES}', file_get_contents("app/templates/{$theme}/libraries.html"), $content);
$content     = str_replace('{class}', isset($_REQUEST['class']) ? $_REQUEST['class'] : '', $content);
$content     = str_replace('{template}', $theme, $content);
//$content     = str_replace('{MENU}', $menu_string, $content);
$content     = str_replace('{lang}', $ini['general']['language'], $content);
$css         = TPage::getLoadedCSS();
$js          = TPage::getLoadedJS();
$content     = str_replace('{HEAD}', $css . $js, $content);

if (TSession::getValue('logged')) {
    $ambiente = new AmbienteConexao();

    $cor = $ambiente->verificarProducao() ? 'blue' : 'red';

    $content = str_replace('{$cor}', $cor, $content);

    switch (SessaoService::buscarTipoUsuario()) {

        case TipoUsuario::ADMIN_REGIONAL:
        case TipoUsuario::FUNCIONARIO_REGIONAL:
            $arquivo = 'menu_regional.xml';
            $content = str_replace('{$paginaInicial}', "HomeRegional", $content);
            break;

        case TipoUsuario::VENDEDOR:
            $arquivo = 'menu_vendedor.xml';
            $content = str_replace('{$paginaInicial}', "HomeVendedor", $content);
            break;

        default:
            $arquivo = 'menu.xml';
            $content = str_replace('{$paginaInicial}', "WelcomeView", $content);
            break;
    }

    $menu    = AdiantiMenuBuilder::parse($arquivo, $theme);
    $content = str_replace('{MENU}', $menu, $content);
} else {
    $content = file_get_contents("app/templates/{$theme}/login.html");
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
