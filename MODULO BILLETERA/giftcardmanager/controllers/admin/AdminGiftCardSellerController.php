<?php

class AdminGiftCardSellerController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        $tpl_path = _PS_MODULE_DIR_.'giftcardmanager/views/templates/admin/seller.tpl';

        $this->context->smarty->assign([
            'submit_url' => $this->context->link->getAdminLink('AdminGiftCardSeller'),
            'confirmations' => $this->confirmations,
            'errors' => $this->errors,
        ]);

        if (isset($this->context->cookie->last_print_data)) {
            $this->context->smarty->assign('print_data', json_decode($this->context->cookie->last_print_data, true));
            unset($this->context->cookie->last_print_data);
            $this->context->cookie->write();
        }

        $this->content .= $this->context->smarty->fetch($tpl_path);
        $this->context->smarty->assign('content', $this->content);
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitConsumeGiftCard')) {
            $code = Tools::getValue('code');
            $seller_name = Tools::getValue('seller_name');
            $amount = (float) Tools::getValue('amount');
            $invoice_number = Tools::getValue('invoice_number');

            if (empty($code) || empty($seller_name) || empty($amount) || empty($invoice_number)) {
                $this->errors[] = $this->l('Todos los campos son obligatorios.');

                return;
            }

            if ($amount <= 0) {
                $this->errors[] = $this->l('El monto debe ser mayor a 0.');

                return;
            }

            $sql = new DbQuery;
            $sql->select('id_giftcard, balance, active');
            $sql->from('giftcard');
            $sql->where('code = \''.pSQL($code).'\'');

            $giftcard_data = Db::getInstance()->getRow($sql);

            if (! $giftcard_data) {
                $this->errors[] = $this->l('La Gift Card no existe.');

                return;
            }

            if (! $giftcard_data['active']) {
                $this->errors[] = $this->l('La Gift Card se encuentra inactiva.');

                return;
            }

            if ($giftcard_data['balance'] < $amount) {
                $this->errors[] = $this->l('Saldo insuficiente en la Gift Card. Saldo actual: $'.number_format($giftcard_data['balance'], 2));

                return;
            }

            // Realizar consumo
            $new_balance = $giftcard_data['balance'] - $amount;

            Db::getInstance()->update('giftcard', [
                'balance' => $new_balance,
                'date_upd' => date('Y-m-d H:i:s'),
            ], 'id_giftcard = '.(int) $giftcard_data['id_giftcard']);

            Db::getInstance()->insert('giftcard_movement', [
                'id_giftcard' => (int) $giftcard_data['id_giftcard'],
                'id_employee' => (int) $this->context->employee->id,
                'seller_name' => pSQL($seller_name),
                'amount' => $amount,
                'invoice_number' => pSQL($invoice_number),
                'date_add' => date('Y-m-d H:i:s'),
            ]);

            // Sincronizar con CartRule
            $id_cart_rule = (int) CartRule::getIdByCode($code);
            if ($id_cart_rule) {
                $cart_rule = new CartRule($id_cart_rule);
                $cart_rule->reduction_amount = $new_balance;
                if ($new_balance <= 0) {
                    $cart_rule->active = 0;
                }
                $cart_rule->update();
            }

            $this->confirmations[] = $this->l('Consumo registrado exitosamente. Nuevo saldo: $').number_format($new_balance, 2);

            $details = "================================\n";
            $details .= 'Fecha: '.date('Y-m-d H:i:s')."\n";
            $details .= 'Factura: '.$invoice_number."\n";
            $details .= 'Vendedor: '.$seller_name."\n";
            $details .= "================================\n";
            $details .= 'GIFT CARD: '.$code."\n";
            $details .= "--------------------------------\n";
            $details .= 'CONSUMO:      $'.number_format($amount, 2)."\n";
            $details .= 'SALDO ACTUAL: $'.number_format($new_balance, 2)."\n";
            $details .= "================================\n";
            $details .= "    ¡Gracias por su visita!     \n";

            $print_data = [
                'title' => 'VERSSATO',
                'subtitle' => 'RECIBO DE CONSUMO',
                'content' => $details,
            ];
            $this->context->cookie->last_print_data = json_encode($print_data);
            $this->context->cookie->write();
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminGiftCardSeller'));
        }
    }

    public function displayAjaxCheckBalance()
    {
        if (ob_get_level() && ob_get_length() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json');

        $code = trim(Tools::getValue('code'));
        if (empty($code)) {
            exit(json_encode(['success' => false, 'message' => 'Código vacío.']));
        }

        $sql = new DbQuery;
        $sql->select('balance, active');
        $sql->from('giftcard');
        $sql->where('code = \''.pSQL($code).'\'');

        $giftcard = Db::getInstance()->getRow($sql);

        if (! $giftcard) {
            exit(json_encode(['success' => false, 'message' => 'Gift card no encontrada.']));
        }

        if (! $giftcard['active']) {
            exit(json_encode(['success' => false, 'message' => 'La Gift Card está inactiva.']));
        }

        exit(json_encode([
            'success' => true,
            'balance' => number_format($giftcard['balance'], 2, '.', ''),
            'message' => 'Saldo disponible: $'.number_format($giftcard['balance'], 2),
        ]));
    }
}
