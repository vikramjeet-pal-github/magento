<?php
namespace Vonnda\GiftOrder\Plugin\OneStepCheckout;

use Magento\Checkout\Api\GuestShippingInformationManagementInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\GiftMessage\Api\CartRepositoryInterface as GiftMessageRepo;

class GuestShippingInformationManagement
{

    const FORM = 'addressInformation';
    const SCOPE = 'shipping_address';
    const CODE = 'gift_recipient_email';

    protected $checkoutSession;
    protected $request;
    protected $serializer;
    protected $giftMessageRepo;

    public function __construct(
        CheckoutSession $checkoutSession,
        RequestInterface $request,
        JsonSerializer $serializer,
        GiftMessageRepo $giftMessageRepo
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
        $this->serializer = $serializer;
        $this->giftMessageRepo = $giftMessageRepo;
    }

    public function beforeSaveAddressInformation(
        GuestShippingInformationManagementInterface $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        $content = $this->request->getContent() ?? '{}';

        $quote = $this->checkoutSession->getQuote();
        if(!$quote->getGiftOrder()){
            return [$cartId, $addressInformation];
        }

        $address = $quote->getShippingAddress();

        /** @var array $data */
       $data = $this->serializer->unserialize($content);

        if(isset($data[self::FORM][self::SCOPE]['customAttributes'])){
            foreach($data[self::FORM][self::SCOPE]['customAttributes'] as $attribute){
                if($attribute['attribute_code'] === self::CODE){
                    $address->setData(self::CODE, $attribute['value']);
                    $address->save();
                }
            }
        }

        return [$cartId, $addressInformation];
    }

}
