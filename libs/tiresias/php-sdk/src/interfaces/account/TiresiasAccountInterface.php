<?php
/**
 * Copyright (c) 2015, BeTechnology Solutions Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors
 * may be used to endorse or promote products derived from this software without
 * specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author BeTechnology Solutions Ltd <info@betechnology.es>
 * @copyright 2015 BeTechnology Solutions Ltd
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

/**
 * Interface for the Tiresias account model that handles creation, syncing and SSO access for the Tiresias account.
 */
interface TiresiasAccountInterface
{
    /**
     * Creates a new Tiresias account with the specified data.
     *
     * @param TiresiasAccountMetaDataInterface $meta the account data model.
     * @return TiresiasAccount the newly created account.
     * @throws TiresiasException if the account cannot be created.
     */
    public static function create(TiresiasAccountMetaDataInterface $meta);

    /**
     * Syncs an existing Tiresias account via Oauth2.
     * Requires that the oauth cycle has already completed the first step in getting the authorization code.
     *
     * @param TiresiasOAuthClientMetaDataInterface $meta the oauth2 client meta data to use for connection to Tiresias.
     * @param string $code the authorization code that grants access to transfer data from tiresias.
     * @return TiresiasAccount the synced account.
     * @throws TiresiasException if the account cannot be synced.
     */
    public static function syncFromTiresias(TiresiasOAuthClientMetaDataInterface $meta, $code);

    /**
     * Notifies Tiresias that an account has been deleted.
     *
     * @throws TiresiasException if the API request to Tiresias fails.
     */
    public function delete();

    /**
     * Gets the account name.
     *
     * @return string the account name.
     */
    public function getName();

    /**
     * Checks if this account has been connected to Tiresias, i.e. all API tokens exist.
     *
     * @return bool true if it is connected, false otherwise.
     */
    public function isConnectedToTiresias();

    /**
     * Gets an api token associated with this account by it's name , e.g. "sso".
     *
     * @param string $name the api token name.
     * @return TiresiasApiToken|null the token or null if not found.
     */
    public function getApiToken($name);

    /**
     * Gets the secured iframe url for the account configuration page.
     *
     * @param TiresiasAccountMetaDataIframeInterface $meta the iframe meta data to use for fetching the secured url.
     * @param array $params optional extra params to add to the iframe url.
     * @return bool|string the url or false if could not be fetched.
     */
    public function getIframeUrl(TiresiasAccountMetaDataIframeInterface $meta, array $params = array());

    /**
     * Signs the user in to Tiresias via SSO.
     * Requires that the account has a valid sso token associated with it.
     *
     * @param TiresiasAccountMetaDataIframeInterface $meta the iframe meta data model.
     * @return string a secure login url that can be used for example to build the config iframe url.
     * @throws TiresiasException if SSO fails.
     */
    public function ssoLogin(TiresiasAccountMetaDataIframeInterface $meta);
}
