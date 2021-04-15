<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\ViewModel;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Backend\Model\Session\Proxy as Session;

class BulkAssignNextOrder implements ArgumentInterface
{

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    protected $session;

    /**
     * @param SourceRepositoryInterface $sourceRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Session $session
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->session = $session;
    }

    public function getSubscriptionCount()
    {
        return count($this->session->getSubscriptionCustomerIds());
    }

    //Get option array

}
