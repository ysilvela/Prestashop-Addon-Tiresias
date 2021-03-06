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

/**
 * Helper class for managing Tiresias accounts.
 */
class TiresiasTaggingHelperAccount
{
	/**
	 * Saves a Tiresias account to PS config.
	 * Also handles any attached API tokens.
	 *
	 * @param TiresiasAccount $account the account to save.
	 * @param null|int $id_lang the ID of the language to set the account name for.
	 * @return bool true if the save was successful, false otherwise.
	 */
	public function save(TiresiasAccount $account, $id_lang)
	{
		/** @var TiresiasTaggingHelperConfig $helper_config */
		$helper_config = Tiresias::helper('tiresias_tagging/config');
		$success = $helper_config->saveAccountName($account->getName(), $id_lang);
		if ($success)
			foreach ($account->getTokens() as $token)
				$success = $success && $helper_config->saveToken($token->getName(), $token->getValue(), $id_lang);
		return $success;
	}

	/**
	 * Deletes a Tiresias account from the PS config.
	 * Also sends a notification to Tiresias that the account has been deleted.
	 *
	 * @param TiresiasAccount $account the account to delete.
	 * @param int $id_lang the ID of the language model to delete the account for.
	 * @param null|int $id_shop_group the ID of the shop context.
	 * @param null|int $id_shop the ID of the shop.
	 * @return bool true if successful, false otherwise.
	 */
	public function delete(TiresiasAccount $account, $id_lang, $id_shop_group = null, $id_shop = null)
	{
		/** @var TiresiasTaggingHelperConfig $helper_config */
		$helper_config = Tiresias::helper('tiresias_tagging/config');
		$success = $helper_config->deleteAllFromContext($id_lang, $id_shop_group, $id_shop);
		if ($success)
		{
			$token = $account->getApiToken('sso');
			if ($token)
				try
				{
					$account->delete();
				}
				catch (TiresiasException $e)
				{
					Tiresias::helper('tiresias_tagging/logger')->error(
						__CLASS__.'::'.__FUNCTION__.' - '.$e->getMessage(),
						$e->getCode()
					);
				}
		}
		return $success;
	}

	/**
	 * Deletes all Tiresias accounts from the system and notifies tiresias that accounts are deleted.
	 *
	 * @return bool
	 */
	public function deleteAll()
	{
		foreach (Shop::getShops() as $shop)
		{
			$id_shop = isset($shop['id_shop']) ? $shop['id_shop'] : null;
			foreach (Language::getLanguages(true, $id_shop) as $language)
			{
				$id_shop_group = isset($shop['id_shop_group']) ? $shop['id_shop_group'] : null;
				$account = $this->find($language['id_lang'], $id_shop_group, $id_shop);
				if ($account === null)
					continue;
				$this->delete($account, $language['id_lang'], $id_shop_group, $id_shop);
			}
		}
		return true;
	}

	/**
	 * Finds and returns an account for given criteria.
	 *
	 * @param null|int $lang_id the ID of the language.
	 * @param null|int $id_shop_group the ID of the shop context.
	 * @param null|int $id_shop the ID of the shop.
	 * @return TiresiasAccount|null the account with loaded API tokens, or null if not found.
	 */
	public function find($lang_id = null, $id_shop_group = null, $id_shop = null)
	{
		/** @var TiresiasTaggingHelperConfig $helper_config */
		$helper_config = Tiresias::helper('tiresias_tagging/config');
		$account_name = $helper_config->getAccountName($lang_id, $id_shop_group, $id_shop);
		if (!empty($account_name))
		{
			$account = new TiresiasAccount($account_name);
			$tokens = array();
			foreach (TiresiasApiToken::getApiTokenNames() as $token_name)
			{
				$token_value = $helper_config->getToken($token_name, $lang_id, $id_shop_group, $id_shop);
				if (!empty($token_value))
					$tokens[$token_name] = $token_value;
			}

			if (!empty($tokens))
				foreach ($tokens as $name => $value)
					$account->addApiToken(new TiresiasApiToken($name, $value));

			return $account;
		}
		return null;
	}

	/**
	 * Checks if an account exists and is "connected to Tiresias" for given criteria.
	 *
	 * @param null|int $lang_id the ID of the language.
	 * @param null|int $id_shop_group the ID of the shop context.
	 * @param null|int $id_shop the ID of the shop.
	 * @return bool true if it does, false otherwise.
	 */
	public function existsAndIsConnected($lang_id = null, $id_shop_group = null, $id_shop = null)
	{
		$account = $this->find($lang_id, $id_shop_group, $id_shop);
		return ($account !== null && $account->isConnectedToTiresias());
	}
}
