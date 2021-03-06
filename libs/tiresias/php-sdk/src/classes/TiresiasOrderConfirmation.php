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
 * Handles sending the order confirmations to Tiresias via the API.
 *
 * Order confirmations can be sent two different ways:
 * - matched orders; where we know the Tiresias customer ID of the user who placed the order
 * - un-matched orders: where we do not know the Tiresias customer ID of the user who placed the order
 *
 * The second option is a fallback and should be avoided as much as possible.
 */
class TiresiasOrderConfirmation
{
    /**
     * Sends the order confirmation to Tiresias.
     *
     * @param TiresiasOrderInterface $order the placed order model.
     * @param TiresiasAccountInterface $account the Tiresias account for the shop where the order was placed.
     * @param null $customerId the Tiresias customer ID of the user who placed the order.
     * @throws TiresiasException on failure.
     * @return true on success.
     */
    public static function send(TiresiasOrderInterface $order, TiresiasAccountInterface $account, $customerId = null)
    {
        if (!empty($customerId)) {
            $path = TiresiasApiRequest::PATH_ORDER_TAGGING;
            $replaceParams = array('{m}' => $account->getName(), '{cid}' => $customerId);
        } else {
            $path = TiresiasApiRequest::PATH_UNMATCHED_ORDER_TAGGING;
            $replaceParams = array('{m}' => $account->getName());
        }
        $request = new TiresiasApiRequest();
        $request->setPath($path);
        $request->setContentType('application/json');
        $request->setReplaceParams($replaceParams);

        $orderData = array(
            'order_number' => $order->getOrderNumber(),
            'order_status_code' => $order->getOrderStatus()->getCode(),
            'order_status_label' => $order->getOrderStatus()->getLabel(),
            'buyer' => array(
                'first_name' => $order->getBuyerInfo()->getFirstName(),
                'last_name' => $order->getBuyerInfo()->getLastName(),
                'email' => $order->getBuyerInfo()->getEmail(),
            ),
            'created_at' => Tiresias::helper('date')->format($order->getCreatedDate()),
            'payment_provider' => $order->getPaymentProvider(),
            'purchased_items' => array(),
        );
        foreach ($order->getPurchasedItems() as $item) {
            $orderData['purchased_items'][] = array(
                'product_id' => $item->getProductId(),
                'quantity' => (int)$item->getQuantity(),
                'name' => $item->getName(),
                'unit_price' => Tiresias::helper('price')->format($item->getUnitPrice()),
                'price_currency_code' => strtoupper($item->getCurrencyCode()),
            );
        }
        $response = $request->post(json_encode($orderData));
        if ($response->getCode() !== 200) {
            Tiresias::throwHttpException('Failed to send order confirmation to Tiresias.', $request, $response);
        }
        return true;
    }
}
