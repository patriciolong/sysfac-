<?php

class GiftCardMovement extends ObjectModel
{
    public $id_giftcard_movement;

    public $id_giftcard;

    public $id_employee;

    public $seller_name;

    public $amount;

    public $invoice_number;

    public $date_add;

    public static $definition = [
        'table' => 'giftcard_movement',
        'primary' => 'id_giftcard_movement',
        'multilang' => false,
        'fields' => [
            'id_giftcard' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'seller_name' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255],
            'amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'invoice_number' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
}
