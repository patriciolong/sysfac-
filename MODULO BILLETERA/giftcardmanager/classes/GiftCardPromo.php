<?php

class GiftCardPromo extends ObjectModel
{
    public $id_promo;

    public $logo;

    public $title;

    public $description;

    public $valid_until;

    public $socials;

    public $creator_name;

    public $active = 1;

    public $date_add;

    public $date_upd;

    public static $definition = [
        'table' => 'giftcard_promo',
        'primary' => 'id_promo',
        'multilang' => false,
        'fields' => [
            'logo' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255],
            'title' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255],
            'description' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'required' => true],
            'valid_until' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255],
            'socials' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'creator_name' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
}
