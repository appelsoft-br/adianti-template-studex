<?php
class SystemProfileView extends TPage
{
    public function __construct()
    {
        parent::__construct();

        $html = new THtmlRenderer('app/resources/profile.html');
        $replaces = array();

        try {
            TTransaction::open('sample');

            $user = new Usuario(SessaoService::buscarIdUsuarioLogado());
            $replaces = $user->toArray();
            $replaces['frontpage'] = $user->frontpage_name;
            $replaces['groupnames'] = $user->getSystemUserGroupNames();

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }

        $html->enableSection('main', $replaces);
        $html->enableTranslation();


        $container = TVBox::pack($html);
        $container->style = 'width:80%';
        parent::add($container);
    }
    public function onEdit()
    {
    }
}
