<?php
/**
 * 2013-2015 BeTechnology Solutions Ltd
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@betechnology.es so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    BeTechnology Solutions Ltd <info@betechnology.es>
 * @copyright 2013-2015 BeTechnology Solutions Ltd
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_'))
	exit;

/**
 * Upgrades the module to version 2.1.0.
 *
 * Updates the customer link table to link the tiresias customer id to the PS id_cart instead of id_customer.
 * Un-registers payment confirmation hooks.
 * Registers order status post update hooks.
 *
 * @param TiresiasTagging $object
 * @return bool
 */
function upgrade_module_2_1_0($object)
{
	$drop_table = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'tiresiastagging_customer_link`';
	$create_table = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'tiresiastagging_customer_link` (
						`id_cart` INT(10) UNSIGNED NOT NULL,
						`id_tiresias_customer` VARCHAR(255) NOT NULL,
						`date_add` DATETIME NOT NULL,
						`date_upd` DATETIME NULL,
						PRIMARY KEY (`id_cart`, `id_tiresias_customer`)
					) ENGINE '._MYSQL_ENGINE_;

	if (_PS_VERSION_ > '1.5')
		$hooks = $object->registerHook('actionObjectDeleteAfter')
			&& $object->unregisterHook('actionPaymentConfirmation');
	else
		$hooks = true;

	// We just drop the table and re-create as it's easier and we don't want the data we loose.
	return Db::getInstance()->execute($drop_table)
		&& Db::getInstance()->execute($create_table)
		&& $object->unregisterHook('paymentConfirm')
		&& $object->registerHook('postUpdateOrderStatus')
		&& $hooks;
}
