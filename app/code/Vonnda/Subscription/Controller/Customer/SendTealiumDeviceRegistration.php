<?php

namespace Vonnda\Subscription\Controller\Customer;

use Vonnda\TealiumTags\Helper\DeviceRegistration;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session;


class SendTealiumDeviceRegistration extends Action
{

    protected $resultJsonFactory;

    protected $session;

    protected $tealiumHelper;

    /**
     * 
     * Address Delete
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        Context $context,
        Session $session,
        DeviceRegistration $tealiumHelper
    ){
        $this->session = $session;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->tealiumHelper = $tealiumHelper;

        parent::__construct($context);   
    }

    public function execute()
    {
        $request = $this->getRequest();
        $params = $request->getParams();
        try{
            if(!isset($params['serial_number']) 
                || !isset($params['sales_channel'])
                || !isset($params['purchase_date'])){
                throw new \Exception("Invalid request");
            }

            $giftOrder = isset($params['purchase_date']) && $params['purchase_date'];
            $this->tealiumHelper->createRegisterDeviceStepOneEvent(
                $params['sales_channel'],
                $params['purchase_date'],
                $params['serial_number'],
                $giftOrder
            );
            return  $this->resultJsonFactory->create()->setData(['status' => 'success', 'message' => "Event sent."]);
        } catch(\Exception $e) {
            $result = $this->resultJsonFactory->create()->setData(['status' => 'error','message' => $e->getMessage()]);
            return $result;
        }
    }

}