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
 * Upgrades the module to version 1.1.0.
 *
 * Creates 'tiresiastagging_customer_link' db table.
 * Registers hooks 'actionPaymentConfirmation', 'displayPaymentTop' and 'displayHome'.
 * Sets default value for "inject category and search page recommendations" to 1.
 * Removes unused "TIRESIASTAGGING_SERVER_ADDRESS" config variable.
 *
 * @param TiresiasTagging $object
 * @return bool
 */
function upgrade_module_1_1_0($object)
{
	$create_table = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'tiresiastagging_customer_link` (
						`id_customer` INT(10) UNSIGNED NOT NULL,
						`id_tiresias_customer` VARCHAR(255) NOT NULL,
						`date_add` DATETIME NOT NULL,
						`date_upd` DATETIME NULL,
						PRIMARY KEY (`id_customer`, `id_tiresias_customer`)
					) ENGINE '._MYSQL_ENGINE_;

	return Db::getInstance()->execute($create_table)
		&& $object->registerHook('actionPaymentConfirmation')
		&& $object->registerHook('displayPaymentTop')
		&& $object->registerHook('displayHome')
		&& Configuration::updateGlobalValue('TIRESIASTAGGING_INJECT_SLOTS', 1)
		&& Configuration::deleteByName('TIRESIASTAGGING_SERVER_ADDRESS')
		&& Configuration::deleteByName('TIRESIASTAGGING_TOP_SELLERS_CMS_ID');
}