<?php

/**
 * 2007-2024 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Antigravity <contact@antigravity.ai>
 *  @copyright 2024 Antigravity
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
if (! defined('_PS_VERSION_')) {
    exit;
}

class Giftcardmanager extends Module
{
    public function __construct()
    {
        $this->name = 'giftcardmanager';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Antigravity';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '8.99.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Gift Card Manager');
        $this->description = $this->l('Administración y consumo de Gift Cards.');
        $this->confirmUninstall = $this->l('¿Está seguro de desinstalar el módulo?');
    }

    public function install()
    {
        return parent::install() &&
            $this->installDB() &&
            $this->installTabs() &&
            $this->registerHook('actionValidateOrder');
    }

    public function hookActionValidateOrder($params)
    {
        $order = $params['order'];
        $cart = $params['cart'];

        $order_cart_rules = $order->getCartRules();
        foreach ($order_cart_rules as $ocr) {
            $id_cart_rule = (int) $ocr['id_cart_rule'];
            $cart_rule = new CartRule($id_cart_rule);

            // Buscar si este código de descuento es una Gift Card nuestra
            $sql = new DbQuery;
            $sql->select('id_giftcard, balance');
            $sql->from('giftcard');
            $sql->where('code = \''.pSQL($cart_rule->code).'\'');

            $giftcard = Db::getInstance()->getRow($sql);

            if ($giftcard) {
                $amount_used = (float) $ocr['value'];
                $new_balance = (float) $giftcard['balance'] - $amount_used;

                // 1. Actualizar la tabla giftcard
                Db::getInstance()->update('giftcard', [
                    'balance' => $new_balance,
                    'date_upd' => date('Y-m-d H:i:s'),
                ], 'id_giftcard = '.(int) $giftcard['id_giftcard']);

                // 2. Registrar el movimiento de la web
                Db::getInstance()->insert('giftcard_movement', [
                    'id_giftcard' => (int) $giftcard['id_giftcard'],
                    'id_employee' => 0, // 0 indica sistema/web
                    'seller_name' => 'Tienda Web (Pedido #'.$order->id.')',
                    'amount' => $amount_used,
                    'invoice_number' => $order->reference,
                    'date_add' => date('Y-m-d H:i:s'),
                ]);

                // 3. Modificar la Regla de Carrito para que sobreviva
                $cart_rule->reduction_amount = $new_balance;
                $cart_rule->quantity = 1; // Restauramos la cantidad para que se pueda seguir usando
                if ($new_balance <= 0) {
                    $cart_rule->active = 0;
                    $cart_rule->quantity = 0;
                }
                $cart_rule->update();
            }
        }
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            $this->uninstallDB() &&
            $this->uninstallTabs();
    }

    public function installDB()
    {
        $sql = [];
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'giftcard` (
            `id_giftcard` int(11) NOT NULL AUTO_INCREMENT,
            `code` varchar(255) NOT NULL,
            `balance` decimal(20,6) NOT NULL DEFAULT "0.000000",
            `active` tinyint(1) NOT NULL DEFAULT "1",
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY  (`id_giftcard`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'giftcard_movement` (
            `id_giftcard_movement` int(11) NOT NULL AUTO_INCREMENT,
            `id_giftcard` int(11) NOT NULL,
            `id_employee` int(11) NOT NULL,
            `seller_name` varchar(255) NOT NULL,
            `amount` decimal(20,6) NOT NULL,
            `invoice_number` varchar(255) NOT NULL,
            `date_add` datetime NOT NULL,
            PRIMARY KEY  (`id_giftcard_movement`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'giftcard_promo` (
            `id_promo` int(11) NOT NULL AUTO_INCREMENT,
            `logo` varchar(255) NOT NULL,
            `title` varchar(255) NOT NULL,
            `description` text NOT NULL,
            `valid_until` varchar(255) NOT NULL,
            `socials` text NOT NULL,
            `creator_name` varchar(255) NOT NULL,
            `active` tinyint(1) NOT NULL DEFAULT "1",
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY  (`id_promo`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'giftcard_promo_print` (
            `id_print` int(11) NOT NULL AUTO_INCREMENT,
            `id_promo` int(11) NOT NULL,
            `seller_name` varchar(255) NOT NULL,
            `quantity` int(11) NOT NULL,
            `date_add` datetime NOT NULL,
            PRIMARY KEY  (`id_print`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }

        return true;
    }

    public function uninstallDB()
    {
        $sql = [];
        $sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'giftcard`';
        $sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'giftcard_movement`';
        $sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'giftcard_promo`';
        $sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'giftcard_promo_print`';

        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }

        return true;
    }

    public function installTabs()
    {
        // Tab Parent
        $tabParent = new Tab;
        $tabParent->class_name = 'AdminGiftCardManagerParent';
        $tabParent->id_parent = 0;
        $tabParent->module = $this->name;
        foreach (Language::getLanguages(true) as $lang) {
            $tabParent->name[$lang['id_lang']] = 'Gestión Gift Cards';
        }
        $tabParent->add();

        // Admin Tab
        $tabAdmin = new Tab;
        $tabAdmin->class_name = 'AdminGiftCard';
        $tabAdmin->id_parent = $tabParent->id;
        $tabAdmin->module = $this->name;
        foreach (Language::getLanguages(true) as $lang) {
            $tabAdmin->name[$lang['id_lang']] = 'Administrador de Gift Cards';
        }
        $tabAdmin->add();

        // Seller Tab
        $tabSeller = new Tab;
        $tabSeller->class_name = 'AdminGiftCardSeller';
        $tabSeller->id_parent = $tabParent->id;
        $tabSeller->module = $this->name;
        foreach (Language::getLanguages(true) as $lang) {
            $tabSeller->name[$lang['id_lang']] = 'Consumo Vendedor';
        }
        $tabSeller->add();

        // Promo Admin Tab
        $tabPromoAdmin = new Tab;
        $tabPromoAdmin->class_name = 'AdminGiftCardPromo';
        $tabPromoAdmin->id_parent = $tabParent->id;
        $tabPromoAdmin->module = $this->name;
        foreach (Language::getLanguages(true) as $lang) {
            $tabPromoAdmin->name[$lang['id_lang']] = 'Admin. Promociones';
        }
        $tabPromoAdmin->add();

        // Promo Seller Tab
        $tabPromoSeller = new Tab;
        $tabPromoSeller->class_name = 'AdminGiftCardPromoPrint';
        $tabPromoSeller->id_parent = $tabParent->id;
        $tabPromoSeller->module = $this->name;
        foreach (Language::getLanguages(true) as $lang) {
            $tabPromoSeller->name[$lang['id_lang']] = 'Impresión Promociones';
        }
        $tabPromoSeller->add();

        return true;
    }

    public function uninstallTabs()
    {
        $tabs = ['AdminGiftCardManagerParent', 'AdminGiftCard', 'AdminGiftCardSeller', 'AdminGiftCardPromo', 'AdminGiftCardPromoPrint'];
        foreach ($tabs as $className) {
            $id_tab = (int) Tab::getIdFromClassName($className);
            if ($id_tab) {
                $tab = new Tab($id_tab);
                $tab->delete();
            }
        }

        return true;
    }
}
