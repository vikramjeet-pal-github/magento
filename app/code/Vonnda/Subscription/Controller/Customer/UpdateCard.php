<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Controller\Customer;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Model\Session;
use StripeIntegration\Payments\Model\Config;
use StripeIntegration\Payments\Helper\Generic;
use StripeIntegration\Payments\Model\StripeCustomer;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class UpdateCard extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{

    /** @var PageFactory */
    protected $resultPageFactory;

    /** @var JsonFactory */
    protected $resultJsonFactory;

    /** @var Config */
    protected $config;

    /** @var Generic */
    protected $helper;

    /** @var StripeCustomer */
    protected $stripeCustomer;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Session $session
     * @param Config $config
     * @param Generic $helper
     * @param StripeCustomer $stripeCustomer
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Session $session,
        Config $config,
        Generic $helper,
        StripeCustomer $stripeCustomer,
        JsonFactory $resultJsonFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
        $this->config = $config;
        $this->helper = $helper;
        $this->stripeCustomer = $stripeCustomer;
        if (!$session->isLoggedIn()) $this->_redirect('customer/account/login');
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        return $this->UpdateCard($params);
    }

    public function UpdateCard($params){
        $stripe = new \Stripe\StripeClient(
            'sk_test_51IVyhJIIsLaKwMMgxLzAaj51cjyjLM93Zi7Wz4TAI6LC3qFe3mMhGqlMoFMl4QEyGsQJuJge5PdsccThDdYWMwtI00pEmGSeQ0'
        );

        $card['name'] =  $params['billingAddress']['firstname'].' '.$params['billingAddress']['lastname'];
        $card['address_city'] =  $params['billingAddress']['city'];
        $card['address_country'] =  $params['billingAddress']['countryId'];
        $card['address_state'] =  $params['billingAddress']['region'];
        $card['address_zip'] =  $params['billingAddress']['postcode'];
        $card['address_line1']=  $params['billingAddress']['street'][0];
        $card['address_line2'] =  @$params['billingAddress']['street'][1];

        $d = $stripe->customers->updateSource(
            'cus_J8FCn5i4Ku3Ko7',
            'card_1IVykUIIsLaKwMMgdktmKgxp',
            $card
        );
    }

    public function UpdateSaveCard($params)
    {
        $result = $this->resultJsonFactory->create();
        try {
            if (empty($params['payment']) || empty($params['payment']['cc_stripejs_token'])) {
                throw new \Exception("Sorry, the card could not be saved. Unable to use Stripe.js.");
            }
            $parts = explode(":", $params['payment']['cc_stripejs_token']);
            if (!$this->helper->isValidToken($parts[0])) {
                throw new \Exception("Sorry, the card could not be saved. Unable to use Stripe.js.");
            }
            try {
                $this->stripeCustomer->createStripeCustomerIfNotExists();
                $card = $this->stripeCustomer->UpdateCart($parts[0]);
                if (!$card) {
                    throw new \Exception("Sorry, the card could not be saved.");
                }
                // Because sometimes it is returned as a nested object
                if (!$card->exp_month) {
                    $expirationDate = $card->card->exp_month . "/" . $card->card->exp_year;
                    $cardString = $card->card->brand . " " . $card->card->last4;
                } else {
                    $expirationDate = $card->exp_month . "/" . $card->exp_year;
                    $cardString = $card->brand . " " . $card->last4;
                }
                $response = [
                    'status' => 'success',
                    'payment_code' => $card->id,
                    'stripe_customer_id' => $this->stripeCustomer->getId(),
                    'expiration_date' => $expirationDate,
                    'card_string' => $cardString
                ];
                $result->setData($response);

            } catch (\Exception $e) {
                $this->helper->logError($e->getMessage());
                $response = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
                return $result->setData($response);
            }
        } catch (\Stripe\Error\Card $e) {
            $result->setData(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (\Error $e) {
            $this->helper->logError($e->getMessage());
            $this->helper->logError($e->getTraceAsString());
            $response = [
                'status' => 'error',
                'message' => 'Sorry, the card could not be saved.',
                'critical_error' => $e->getMessage()
            ];
            return $result->setData($response);
        } catch (\Exception $e) {
            $result->setData(['status' => 'error', 'message' => $e->getMessage()]);
            $this->helper->logError($e->getMessage());
            $this->helper->logError($e->getTraceAsString());
        }
        return $result;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
