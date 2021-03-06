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
 * Iframe helper class for account administration iframe.
 */
class TiresiasHelperIframe extends TiresiasHelper
{
    const IFRAME_URI_INSTALL = '/api/hub/{platform}/install';
    const IFRAME_URI_UNINSTALL = '/api/hub/{platform}/uninstall';

    /**
     * Returns the url for the account administration iframe.
     * If the passed account is null, then the url will point to the start page where a new account can be created.
     *
     * @param TiresiasAccountMetaDataIframeInterface $meta the iframe meta data.
     * @param TiresiasAccount|null $account the account to return the url for.
     * @param array $params additional parameters to add to the iframe url.
     * @return string the iframe url.
     * @throws TiresiasException if the url cannot be created.
     */
    public function getUrl(
        TiresiasAccountMetaDataIframeInterface $meta,
        TiresiasAccount $account = null,
        array $params = array()
    ) {
        PrestaShopLogger::addLog('TiresiasHelperIframe.getUrl. Recuperamos la url de instalacion o desistalacion ' , 1);
        $queryParams = http_build_query(
            array_merge(
                array(
                    'lang' => strtolower($meta->getLanguageIsoCode()),
                    'ps_version' => $meta->getVersionPlatform(),
                    'nt_version' => $meta->getVersionModule(),
                    'product_pu' => $meta->getPreviewUrlProduct(),
                    'category_pu' => $meta->getPreviewUrlCategory(),
                    'search_pu' => $meta->getPreviewUrlSearch(),
                    'cart_pu' => $meta->getPreviewUrlCart(),
                    'front_pu' => $meta->getPreviewUrlFront(),
                    'shop_lang' => strtolower($meta->getLanguageIsoCodeShop()),
                    'shop_name' => $meta->getShopName(),
                    'unique_id' => $meta->getUniqueId(),
                    'fname' => $meta->getFirstName(),
                    'lname' => $meta->getLastName(),
                    'email' => $meta->getEmail(),
                ),
                $params
            )
        );

        if ($account !== null && $account->isConnectedToTiresias()) {
            try {
                $url = $account->ssoLogin($meta).'?'.$queryParams;
            } catch (TiresiasException $e) {
                // If the SSO fails, we show a "remove account" page to the user in order to
                // allow to remove Tiresias and start over.
                // The only case when this should happen is when the api token for some
                // reason is invalid, which is the case when switching between environments.
                PrestaShopLogger::addLog('TiresiasHelperIframe.getUrl. Ha fallado ssoLogin. Lanzamos la desistalacion ' , 1);
                $url = TiresiasHttpRequest::buildUri(
                    $this->getBaseUrl().self::IFRAME_URI_UNINSTALL.'?'.$queryParams,
                    array(
                        '{platform}' => $meta->getPlatform(),
                    )
                );
            }
        } else {
            PrestaShopLogger::addLog('TiresiasHelperIframe.getUrl. La cuenta no esta conectada. Lanzamos la desistalacion ' , 1);
            $url = TiresiasHttpRequest::buildUri(
                $this->getBaseUrl().self::IFRAME_URI_INSTALL.'?'.$queryParams,
                array(
                    '{platform}' => $meta->getPlatform(),
                )
            );
        }

        return $url;
    }

    /**
     * Returns the base url for the Tiresias iframe.
     *
     * @return string the url.
     */
    protected function getBaseUrl()
    {
        return Tiresias::getEnvVariable('TIRESIAS_WEB_HOOK_BASE_URL', TiresiasHttpRequest::$baseUrl);
    }
}
