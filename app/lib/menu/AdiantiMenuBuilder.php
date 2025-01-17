<?php

use Adianti\Widget\Base\TElement;
use Adianti\Widget\Menu\TMenu;

class AdiantiMenuBuilder
{
    public static function parse($file, $theme)
    {
        if (!in_array('SimpleXML', get_loaded_extensions())) {
            throw new Exception(_t('Extension not found: ^1', 'SimpleXML'));
        }

        $callbackPermissoes = array('SystemPermission', 'checkPermission');

        switch ($theme) {
            case 'theme3':
                ob_start();
                $xml = new SimpleXMLElement(file_get_contents($file));
                $menu = new TMenu($xml, $callbackPermissoes, 1, 'treeview-menu', 'treeview', '');
                $menu->class = 'sidebar-menu';
                $menu->id    = 'side-menu';
                $menu->show();
                $menu_string = ob_get_clean();
                return $menu_string;
                break;
            default:
                ob_start();
                $xml = new SimpleXMLElement(file_get_contents($file));
                $menu = new TMenu($xml, $callbackPermissoes, 1, 'ml-menu', 'x', 'menu-toggle waves-effect waves-block');

                $li = new TElement('li');
                $li->{'class'} = 'active';
                $menu->add($li);

                $li = new TElement('li');
                $li->add('MENU');
                $li->{'class'} = 'header';
                $menu->add($li);

                $menu->class = 'list';
                $menu->style = 'overflow: hidden; width: auto; height: 390px;';
                $menu->show();
                $menu_string = ob_get_clean();
                return $menu_string;
                break;
        }
    }
}
