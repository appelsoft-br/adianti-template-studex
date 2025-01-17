<?php

/**
 * SystemGroupList Listing
 * @author  <your name here>
 */
class SystemGroupList extends TStandardList
{
    protected $form;     // registration form
    protected $datagrid; // listing
    protected $pageNavigation;
    protected $formgrid;
    protected $deleteButton;
    protected $transformCallback;

    /**
     * Page constructor
     */
    public function __construct()
    {
        parent::__construct();

        parent::setDatabase('sample');            // defines the database
        parent::setActiveRecord('SystemGroup');   // defines the active record
        parent::setDefaultOrder('cliente,funcionario,cliente_usuario', 'asc');         // defines the default order
        parent::addFilterField('id', '=', 'id'); // filterField, operator, formField
        parent::addFilterField('name', 'like', 'name'); // filterField, operator, formField
        parent::addFilterField('funcionario', '=', 'funcionario');
        parent::setLimit(30);
        parent::addFilterField('cliente', '=', 'cliente');
        parent::addFilterField('cliente_usuario', '=', 'cliente_usuario');

        // creates the form
        $this->form = new BootstrapFormBuilder('form_search_SystemGroup');
        $this->form->setFormTitle(_t('Groups'));

        // create the form fields
        $id = new TEntry('id');
        $name = new TEntry('name');
        $funcionario = new TEntry('funcionario');
        $cliente = new TEntry('cliente');
        $cliente_usuario = new TEntry('cliente_usuario');

        // add the fields
        $this->form->addFields([new TLabel('Id')], [$id]);
        $this->form->addFields([new TLabel(_t('Name'))], [$name]);
        $this->form->addFields([new TLabel('Funcionários?')], [$funcionario]);
        $this->form->addFields([new TLabel('Regionais?')], [$cliente]);
        $this->form->addFields([new TLabel('Usuários de Regionais')], [$cliente_usuario]);

        $id->setSize('30%');
        $name->setSize('70%');

        // keep the form filled during navigation with session data
        $this->form->setData(TSession::getValue('SystemGroup_filter_data'));

        // add the search form actions
        $this->form->addAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addAction(_t('New'),  new TAction(array('SystemGroupForm', 'onEdit')), 'fa:plus green');

        // creates a DataGrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        // creates the datagrid columns
        $column_id = new TDataGridColumn('id', 'Id', 'center', 50);
        $column_name = new TDataGridColumn('name', _t('Name'), 'left');
        $column_funcionario_regional = new TDataGridColumn('cliente_usuario', 'Funcionário Regional', 'left');
        $column_regional = new TDataGridColumn('cliente', 'Regional', 'left');
        $column_matriz = new TDataGridColumn('funcionario', 'Funcionário Matriz', 'left');

        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_name);
        $this->datagrid->addColumn($column_funcionario_regional);
        $this->datagrid->addColumn($column_regional);
        $this->datagrid->addColumn($column_matriz);


        // creates the datagrid column actions
        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'id');
        $column_id->setAction($order_id);

        $order_name = new TAction(array($this, 'onReload'));
        $order_name->setParameter('order', 'name');
        $column_name->setAction($order_name);

        // create EDIT action
        $action_edit = new TDataGridAction(array('SystemGroupForm', 'onEdit'));
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('far:edit blue fa-lg');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);

        // create DELETE action
        $action_del = new TDataGridAction(array($this, 'onDelete'));
        $action_del->setButtonClass('btn btn-default');
        $action_del->setLabel(_t('Delete'));
        $action_del->setImage('far:trash-alt red fa-lg');
        $action_del->setField('id');
        $this->datagrid->addAction($action_del);

        // create the datagrid model
        $this->datagrid->createModel();

        // create the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add(TPanelGroup::pack('', $this->datagrid));
        $container->add($this->pageNavigation);

        parent::add($container);
    }
}
