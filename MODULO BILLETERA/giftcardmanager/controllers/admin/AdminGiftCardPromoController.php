<?php

require_once _PS_MODULE_DIR_.'giftcardmanager/classes/GiftCardPromo.php';

class AdminGiftCardPromoController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'giftcard_promo';
        $this->className = 'GiftCardPromo';
        $this->identifier = 'id_promo';

        parent::__construct();

        $this->fields_list = [
            'id_promo' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'logo' => [
                'title' => $this->l('Logo (Título Principal)'),
            ],
            'title' => [
                'title' => $this->l('Descuento/Título'),
            ],
            'creator_name' => [
                'title' => $this->l('Creador'),
            ],
            'active' => [
                'title' => $this->l('Activo'),
                'active' => 'status',
                'type' => 'bool',
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Eliminar seleccionados'),
                'confirm' => $this->l('¿Eliminar elementos seleccionados?'),
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
                'title' => $this->l('Gestión de Promoción'),
                'icon' => 'icon-star',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Logo / Encabezado'),
                    'name' => 'logo',
                    'desc' => $this->l('Ej. VERSSATO'),
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Título de Promoción'),
                    'name' => 'title',
                    'desc' => $this->l('Ej. 50% OFF o ¡Tu siguiente par tiene!'),
                    'required' => true,
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->l('Descripción'),
                    'name' => 'description',
                    'desc' => $this->l('Ej. Presenta este cupón en tu próxima compra.'),
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Texto Validez'),
                    'name' => 'valid_until',
                    'desc' => $this->l('Ej. 31 AGOSTO'),
                    'required' => true,
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->l('Redes Sociales'),
                    'name' => 'socials',
                    'desc' => $this->l('Ej. IG: @verssato.ec'),
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Nombre del Creador'),
                    'name' => 'creator_name',
                    'desc' => $this->l('Obligatorio para auditoría.'),
                    'required' => true,
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
        $id_promo = (int) Tools::getValue('id_promo');
        $promo = new GiftCardPromo($id_promo);

        $sql = new DbQuery;
        $sql->select('*');
        $sql->from('giftcard_promo_print');
        $sql->where('id_promo = '.(int) $id_promo);
        $sql->orderBy('date_add DESC');

        $prints = Db::getInstance()->executeS($sql);

        $total_printed = 0;
        foreach ($prints as $p) {
            $total_printed += (int) $p['quantity'];
        }

        $this->context->smarty->assign([
            'promo' => $promo,
            'prints' => $prints,
            'total_printed' => $total_printed,
        ]);

        $tpl_path = _PS_MODULE_DIR_.'giftcardmanager/views/templates/admin/promo_view.tpl';

        return $this->context->smarty->fetch($tpl_path);
    }
}
