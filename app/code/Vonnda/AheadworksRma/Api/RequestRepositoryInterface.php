<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\AheadworksRma\Api;

/**
 * Request CRUD interface
 */
interface RequestRepositoryInterface extends \Aheadworks\Rma\Api\RequestRepositoryInterface
{
    /**
     * Retrieve request by id
     *
     * @param int $requestId
     * @param bool $noCache
     * @return \Vonnda\AheadworksRma\Api\Data\RequestInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($requestId, $noCache = false);

    /**
     * Retrieve request matching the specified criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vonnda\AheadworksRma\Api\Data\RequestSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
