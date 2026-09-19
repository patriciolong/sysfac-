<?php

require_once _PS_MODULE_DIR_.'giftcardmanager/classes/GiftCard.php';

class AdminGiftCardController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'giftcard';
        $this->className = 'GiftCard';
        $this->identifier = 'id_giftcard';

        parent::__construct();

        $this->fields_list = [
            'id_giftcard' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'code' => [
                'title' => $this->l('Código'),
            ],
            'balance' => [
                'title' => $this->l('Saldo'),
                'type' => 'price',
                'currency' => true,
            ],
            'active' => [
                'title' => $this->l('Activo'),
                'active' => 'status',
                'type' => 'bool',
            ],
            'date_add' => [
                'title' => $this->l('Fecha de Creación'),
                'type' => 'datetime',
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash',
            ],
        ];

        $this->addRowAction('view');
        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Gift Card'),
                'icon' => 'icon-gift',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Código'),
                    'name' => 'code',
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Saldo'),
                    'name' => 'balance',
                    'required' => true,
                    'class' => 'fixed-width-xl',
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Activo'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Sí'),
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Guardar'),
            ],
        ];

        return parent::renderForm();
    }

    public function renderView()
    {
        $id_giftcard = (int) Tools::getValue('id_giftcard');
        $giftcard = new GiftCard($id_giftcard);

        $sql = new DbQuery;
        $sql->select('m.*, e.firstname, e.lastname');
        $sql->from('giftcard_movement', 'm');
        $sql->leftJoin('employee', 'e', 'm.id_employee = e.id_employee');
        $sql->where('m.id_giftcard = '.(int) $id_giftcard);
        $sql->orderBy('m.date_add DESC');

        $movements = Db::getInstance()->executeS($sql);

        $this->context->smarty->assign([
            'giftcard' => $giftcard,
            'movements' => $movements,
        ]);

        $tpl_path = _PS_MODULE_DIR_.'giftcardmanager/views/templates/admin/view.tpl';

        return $this->context->smarty->fetch($tpl_path);
    }
}
