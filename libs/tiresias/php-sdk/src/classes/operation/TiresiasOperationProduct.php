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
 * Handles create/update/delete of products through the Tiresias API.
 */
class TiresiasOperationProduct
{
    /**
     * @var TiresiasAccountInterface the account to perform the operation on.
     */
    protected $account;

    /**
     * @var TiresiasProductCollection collection object of products to perform the operation on.
     */
    protected $collection;

    /**
     * Constructor.
     *
     * Accepts the account for which the product operation is to be performed on.
     *
     * @param TiresiasAccountInterface $account the account object.
     */
    public function __construct(TiresiasAccountInterface $account)
    {
        $this->account = $account;
        $this->collection = new TiresiasProductCollection();
    }

    /**
     * Adds a product tho the collection on which the operation is the performed.
     *
     * @param TiresiasProductInterface $product
     */
    public function addProduct(TiresiasProductInterface $product)
    {
        $this->collection[] = $product;
    }

    /**
     * Sends a POST request to create or update all the products currently in the collection.
     *
     * @return bool if the request was successful.
     * @throws TiresiasException on failure.
     */
    public function upsert()
    {
        $request = $this->initApiRequest();
        $request->setPath(TiresiasApiRequest::PATH_PRODUCTS_UPSERT);
        $response = $request->post($this->getCollectionAsJson());
        if ($response->getCode() !== 200) {
            Tiresias::throwHttpException('Failed to upsert Tiresias product(s).', $request, $response);
        }
        return true;
    }

    /**
     * Sends a POST request to create all the products currently in the collection.
     *
     * @return bool if the request was successful.
     * @throws TiresiasException on failure.
     */
    public function create()
    {
        $request = $this->initApiRequest();
        $request->setPath(TiresiasApiRequest::PATH_PRODUCTS_CREATE);
        $response = $request->post($this->getCollectionAsJson());
        if ($response->getCode() !== 200) {
            Tiresias::throwHttpException('Failed to create Tiresias product(s).', $request, $response);
        }
        return true;
    }

    /**
     * Sends a PUT request to update all the products currently in the collection.
     *
     * @return bool if the request was successful.
     * @throws TiresiasException on failure.
     */
    public function update()
    {
        $request = $this->initApiRequest();
        $request->setPath(TiresiasApiRequest::PATH_PRODUCTS_UPDATE);
        $response = $request->put($this->getCollectionAsJson());
        if ($response->getCode() !== 200) {
            Tiresias::throwHttpException('Failed to update Tiresias product(s).', $request, $response);
        }
        return true;
    }

    /**
     * Sends a POST request to delete all the products currently in the collection.
     *
     * @return bool if the request was successful.
     * @throws TiresiasException on failure.
     */
    public function delete()
    {
        $request = $this->initApiRequest();
        $request->setPath(TiresiasApiRequest::PATH_PRODUCTS_DISCONTINUE);
        $response = $request->post($this->getCollectionIdsAsJson());
        if ($response->getCode() !== 200) {
            Tiresias::throwHttpException('Failed to delete Tiresias product(s).', $request, $response);
        }
        return true;
    }

    /**
     * Create and returns a new API request object initialized with:
     * - content type
     * - auth token
     *
     * @return TiresiasApiRequest the newly created request object.
     * @throws TiresiasException if the account does not have the `products` token set.
     */
    protected function initApiRequest()
    {
        $token = $this->account->getApiToken('products');
        if (is_null($token)) {
            throw new TiresiasException('No `products` API token found for account.');
        }

        $request = new TiresiasApiRequest();
        $request->setContentType('application/json');
        $request->setAuthBasic('', $token->getValue());
        return $request;
    }

    /**
     * Converts the product object into an array and returns it.
     *
     * @param TiresiasProductInterface $product the object.
     * @return array the newly created array.
     */
    protected function getProductAsArray(TiresiasProductInterface $product)
    {
        return array(
            'url' => $product->getUrl(),
            'product_id' => $product->getProductId(),
            'name' => $product->getName(),
            'image_url' => $product->getImageUrl(),
            'price' => Tiresias::helper('price')->format($product->getPrice()),
            'list_price' => Tiresias::helper('price')->format($product->getListPrice()),
            'price_currency_code' => strtoupper($product->getCurrencyCode()),
            'availability' => $product->getAvailability(),
            'tag1' => $product->getTags(),
            'categories' => $product->getCategories(),
            'description' => $product->getShortDescription().'<br/>'.$product->getDescription(),
            'brand' => $product->getBrand(),
            'date_published' => Tiresias::helper('date')->format($product->getDatePublished()),
        );
    }

    /**
     * Returns the whole collection in JSON format.
     *
     * @return string the json.
     * @throws TiresiasException if the collection is empty.
     */
    protected function getCollectionAsJson()
    {
        $data = array();
        foreach ($this->collection->getArrayCopy() as $item) {
            /** @var TiresiasProductInterface|TiresiasValidatableInterface $item */
            $validator = new TiresiasValidator($item);
            if ($validator->validate()) {
                $data[] = $this->getProductAsArray($item);
            }
        }
        if (empty($data)) {
            throw new TiresiasException('No products found in collection.');
        }
        return json_encode($data);
    }

    /**
     * Returns all the product IDs of the items in the collection in JSON format.
     *
     * @return string the json.
     * @throws TiresiasException if the collection is empty.
     */
    protected function getCollectionIdsAsJson()
    {
        $data = array();
        foreach ($this->collection->getArrayCopy() as $item) {
            /** @var TiresiasProductInterface $item */
            $data[] = $item->getProductId();
        }
        if (empty($data)) {
            throw new TiresiasException('No products found in collection.');
        }
        return json_encode($data);
    }
}
