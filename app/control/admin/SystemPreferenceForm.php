<?php

use Adianti\Base\TStandardForm;
use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Database\TTransaction;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Wrapper\BootstrapFormBuilder;

/**
 * SystemPreferenceForm
 *
 * @version    8.0
 * @package    control
 * @subpackage admin
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license-template
 */
class SystemPreferenceForm extends TStandardForm
{
    protected $form; // formulário

    /**
     * método construtor
     * Cria a página e o formulário de cadastro
     */
    function __construct()
    {
        parent::__construct();

        $this->setDatabase('sample');
        $this->setActiveRecord('SystemPreference');

        // cria o formulário
        $this->form = new BootstrapFormBuilder('form_preferences');
        $this->form->setFormTitle(_t('Preferences'));

        $flag_permite_venda_negativado   = new TCombo('flag_permite_venda_negativado');

        $yesno = array();
        $yesno['1'] = _t('Yes');
        $yesno['0'] = _t('No');

        $flag_permite_venda_negativado->addItems($yesno);

        $this->form->appendPage('Financeiro');
        $this->form->addFields([new TLabel('Permite Venda para Clientes com Pendências Financeiras?')], [$flag_permite_venda_negativado]);

        $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave')), 'far:save');

        $container = new TVBox;
        $container->{'style'} = 'width: 90%; max-width: 1200px';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        parent::add($container);
    }

    /**
     * Carrega o formulário de preferências
     */
    function onEdit($param)
    {
        try {
            // open a transaction with database
            TTransaction::open($this->database);

            $preferences = SystemPreference::getAllPreferences();
            if ($preferences) {
                $this->form->setData((object) $preferences);
            }

            // close the transaction
            TTransaction::close();
        } catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }

    /**
     * method onSave()
     * Executed whenever the user clicks at the save button
     */
    function onSave()
    {
        try {
            // open a transaction with database
            TTransaction::open($this->database);

            // get the form data
            $data = $this->form->getData();
            $data_array = (array) $data;

            foreach ($data_array as $property => $value) {
                $object = new SystemPreference;
                $object->{'id'}    = $property;
                $object->{'value'} = $value;
                $object->store();
            }

            // fill the form with the active record data
            $this->form->setData($data);

            // close the transaction
            TTransaction::close();

            // shows the success message
            new TMessage('info', AdiantiCoreTranslator::translate('Record saved'));
            // reload the listing
        } catch (Exception $e) // in case of exception
        {
            // get the form data
            $object = $this->form->getData($this->activeRecord);

            // fill the form with the active record data
            $this->form->setData($object);

            // shows the exception error message
            new TMessage('error', $e->getMessage());

            // undo all pending operations
            TTransaction::rollback();
        }
    }
}
