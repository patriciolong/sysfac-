<?php

class AdminGiftCardPromoPrintController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        $sql = new DbQuery;
        $sql->select('*');
        $sql->from('giftcard_promo');
        $sql->where('active = 1');
        $sql->orderBy('id_promo DESC');

        $promos = Db::getInstance()->executeS($sql);

        $tpl_path = _PS_MODULE_DIR_.'giftcardmanager/views/templates/admin/promo_print.tpl';

        $this->context->smarty->assign([
            'submit_url' => $this->context->link->getAdminLink('AdminGiftCardPromoPrint'),
            'promos' => $promos,
            'confirmations' => $this->confirmations,
            'errors' => $this->errors,
        ]);

        if (isset($this->context->cookie->promo_print_data)) {
            $this->context->smarty->assign('print_data', json_decode($this->context->cookie->promo_print_data, true));
            unset($this->context->cookie->promo_print_data);
            $this->context->cookie->write();
        }

        $this->content .= $this->context->smarty->fetch($tpl_path);
        $this->context->smarty->assign('content', $this->content);
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitPrintPromos')) {
            $id_promo = (int) Tools::getValue('id_promo');
            $seller_name = trim(Tools::getValue('seller_name'));
            $quantity = (int) Tools::getValue('quantity');

            if (empty($id_promo) || empty($seller_name) || $quantity <= 0) {
                $this->errors[] = $this->l('Todos los campos son obligatorios y la cantidad debe ser mayor a 0.');

                return;
            }

            $sql = new DbQuery;
            $sql->select('*');
            $sql->from('giftcard_promo');
            $sql->where('id_promo = '.(int) $id_promo);
            $promo = Db::getInstance()->getRow($sql);

            if (! $promo) {
                $this->errors[] = $this->l('La promoción seleccionada no existe.');

                return;
            }

            // Registro de auditoría
            Db::getInstance()->insert('giftcard_promo_print', [
                'id_promo' => (int) $id_promo,
                'seller_name' => pSQL($seller_name),
                'quantity' => $quantity,
                'date_add' => date('Y-m-d H:i:s'),
            ]);

            // Generar identificador de lote (opcional) o código base
            $base_code = 'P'.$id_promo.'-'.date('ymd');

            $print_data = [
                'type' => 'standard',
                'logo' => $promo['logo'],
                'promo_title' => $promo['title'], // Utilizamos el título corto como principal
                'promo_discount' => '', // Si hay un descuento específico
                'description' => $promo['description'],
                'valid_until' => $promo['valid_until'],
                'socials' => $promo['socials'],
                'quantity' => $quantity, // Lo pasamos a la vista para el loop
                'base_code' => $base_code,
            ];

            $this->context->cookie->promo_print_data = json_encode($print_data);
            $this->context->cookie->write();
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminGiftCardPromoPrint'));
        }
    }
}
