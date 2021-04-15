<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Block\Adminhtml\SubscriptionPlan;

use Vonnda\Subscription\Model\SubscriptionPlanRepository;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\RequestInterface;


class InfoBlock extends Template
{
    /**
     * Subscription Customer Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPlanRepository $subscriptionPlanRepository
     */
    protected $subscriptionPlanRepository;

    /**
     * Request Object
     *
     * @var \Magento\Framework\App\RequestInterface $request
     */
    protected $request;

    /**
     * 
     * Subscription Customer Info Block Constructor
     * 
     * @param Context $context
     * @param RequestInterface $request
     * @param SubscriptionPlanRepository $subscriptionPlanRepository
     * 
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        SubscriptionPlanRepository $subscriptionPlanRepository
    ){
        $this->request = $request;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        parent::__construct($context);
	}

    public function getSubscriptionPlan()
    {
        $id = $this->request->getParam('id');
        if($id){
            try {
                return $this->subscriptionPlanRepository->getById($id);
            } catch(\Exception $e){
                return false;
            }
        } else {
            return false;
        }
    }

}