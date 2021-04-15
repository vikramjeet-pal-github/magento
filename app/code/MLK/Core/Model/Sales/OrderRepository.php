<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace MLK\Core\Model\Sales;

use MLK\Core\Api\Sales\Data\OrderInterface;
use MLK\Core\Api\Sales\OrderRepositoryInterface;

use Magento\Sales\Model\OrderRepository as CoreOrderRepository;
/**
 * Repository class
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderRepository implements OrderRepositoryInterface
{
    protected $coreOrderRepository;

    public function __construct(
        CoreOrderRepository $coreOrderRepository
    ){
        $this->coreOrderRepository = $coreOrderRepository;
    }
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
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        return $this->coreOrderRepository->getList($searchCriteria);
    }

    /**
     * load entity
     *
     * @param int $id
     * @return \MLK\Core\Api\Sales\Data\OrderInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($id)
    {
        return $this->coreOrderRepository->get($id);
    }

}
