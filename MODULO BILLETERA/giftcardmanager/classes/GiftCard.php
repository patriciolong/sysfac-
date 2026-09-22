<?php

class GiftCard extends ObjectModel
{
    public $id_giftcard;

    public $code;

    public $balance;

    public $active = 1;

    public $date_add;

    public $date_upd;

    public static $definition = [
        'table' => 'giftcard',
        'primary' => 'id_giftcard',
        'multilang' => false,
        'fields' => [
            'code' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255],
            'balance' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public function add($auto_date = true, $null_values = false)
    {
        $res = parent::add($auto_date, $null_values);
        if ($res) {
            $cart_rule = new CartRule;
            $cart_rule->code = $this->code;
            $cart_rule->name = [];
            foreach (Language::getLanguages(false) as $lang) {
                $cart_rule->name[$lang['id_lang']] = 'Gift Card '.$this->code;
            }
            $cart_rule->description = 'Creado desde el módulo GiftCardManager';
            $cart_rule->id_customer = 0;
            $cart_rule->date_from = date('Y-m-d H:i:s');
            $cart_rule->date_to = date('Y-m-d H:i:s', strtotime('+1 year'));
            $cart_rule->quantity = 1;
            $cart_rule->quantity_per_user = 1;
            $cart_rule->reduction_amount = $this->balance;
            $cart_rule->reduction_tax = 1;
            $cart_rule->partial_use = 0; // Desactivado para mantener el mismo código
            $cart_rule->active = $this->active;

            $cart_rule->add();
        }

        return $res;
    }
}
