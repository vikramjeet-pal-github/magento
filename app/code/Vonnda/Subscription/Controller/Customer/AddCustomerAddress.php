<?php 

namespace Vonnda\Subscription\Controller\Customer;  

use Vonnda\Subscription\Helper\AddressHelper;

use Carbon\Carbon;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;

//Address and Payment
class AddCustomerAddress extends Action {

    /**
     * Json Factory
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Address Helper
     *
     * @var \Vonnda\Subscription\Helper\AddressHelper $addressHelper
     */
    protected $addressHelper;

    /**
     * Message Manager
     *
     * @var \Magento\Framework\Message\ManagerInterface $messageManager
     */
    protected $messageManager;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AddressHelper $addressHelper,
        ManagerInterface $messageManager
    ){
        $this->resultJsonFactory = $resultJsonFactory;
        $this->addressHelper = $addressHelper;
        $this->messageManager = $messageManager;
        parent::__construct($context);
    }

    public function execute() { 
        $result = $this->resultJsonFactory->create();
        if ($this->getRequest()->isAjax()) 
        {
            $params = $this->getRequest()->getParams();
            $isValidRequest = true;
            $customerId = intval($params['customerId']);
            $addressFields = $params['addressFields'];
            $addressFields['street'] = [$addressFields['streetOne'], $addressFields['streetTwo']];
            unset($addressFields['streetOne']);
            unset($addressFields['streetTwo']);

            if($isValidRequest){
                try {
                    $addressFields['customer_id'] = $customerId;
                    $addressInterface = $this->addressHelper->createAddressInterfaceFromData($addressFields);
                    $address = $this->addressHelper->createNewAddress($addressInterface);
                    $street = $address->getStreet();
                    $streetOne = $street[0];
                    $streetTwo = isset($street[1]) ? $street[1] : "";
                    $region = $address->getRegion();

                    $addressFields = [
                        "id" => $address->getId(),
                        "firstname" => $address->getFirstname(),
                        "lastname" => $address->getLastname(),
                        "streetOne" => $streetOne,
                        "streetTwo" => $streetTwo,
                        "city" => $address->getCity(),
                        "country_id" => $address->getCountryId(),
                        "postcode" => $address->getPostcode(),
                        "telephone" => $address->getTelephone(),
                        "regioncode" => $region->getRegionCode()
                    ];
                    $response = [
                        'Status'=>'success',
                        'customerId' =>  $customerId,
                        'address' => $addressFields];
                } catch(\Exception $e){
                    $response = [
                        'Status'=>'error', 
                        'message' => $e->getMessage()];
                }
                $this->messageManager->addSuccess( __('Thanks for the update.'));
                return $result->setData($response);
            } else {
                $response = [
                    'Status'=>'error', 
                    'message' => 'Improper request'];
                return $result->setData($response);

            }
        }
    } 

}