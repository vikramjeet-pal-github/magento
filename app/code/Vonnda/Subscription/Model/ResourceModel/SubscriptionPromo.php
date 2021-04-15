<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Model\AbstractModel;
use Magento\SalesRule\Model\RuleRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;

class SubscriptionPromo extends AbstractDb
{
	/**
     * Rule Repository
     *
     * @var \Magento\SalesRule\Model\RuleRepository $ruleRepository
     */
	protected $ruleRepository;
	
	/**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

	public function __construct(
		Context $context,
		RuleRepository $ruleRepository,
		SearchCriteriaBuilder $searchCriteriaBuilder
	)
	{
		$this->ruleRepository = $ruleRepository;
		$this->searchCriteriaBuilder = $searchCriteriaBuilder;
		parent::__construct($context);
	}
	
	protected function _construct()
	{
		$this->_init('vonnda_subscription_promo', 'id');
	}

	protected function _beforeSave(AbstractModel $object)
    {
			//Some validation here

			return parent::_beforeSave($object);
    }
	
}