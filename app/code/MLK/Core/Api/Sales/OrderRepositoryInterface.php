<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MLK\Core\Api\Sales;

use Magento\Sales\Api\OrderRepositoryInterface as CoreOrderRepositoryInterface;

/**
 * Order repository interface.
 *
 * An order is a document that a web store issues to a customer. Magento generates a sales order that lists the product
 * items, billing and shipping addresses, and shipping and payment methods. A corresponding external document, known as
 * a purchase order, is emailed to the customer.
 * @api
 * @since 100.0.2
 */
interface OrderRepositoryInterface
{
    /**
     * Lists orders that match specified search criteria.
     *
     * This call returns an array of objects, but detailed information about each object’s attributes might not be
     * included. See https://devdocs.magento.com/codelinks/attributes.html#OrderRepositoryInterface to
     * determine which call to use to get detailed information about all attributes for an object.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria The search criteria.
     * @return \MLK\Core\Api\Sales\Data\OrderSearchResultInterface Order search result interface.
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);


    /**
     * Loads a specified order.
     *
     * @param int $id The order ID.
     * @return \MLK\Core\Api\Sales\Data\OrderInterface Order interface.
     */
    public function get($id);


}
