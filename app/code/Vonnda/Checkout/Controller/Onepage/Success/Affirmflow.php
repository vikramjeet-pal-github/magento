<?php

namespace Vonnda\Checkout\Controller\Onepage\Success;

use Error;
use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Stripe\Error\Card;
use StripeIntegration\Payments\Helper\Generic;
use StripeIntegration\Payments\Model\Config;
use StripeIntegration\Payments\Model\ResourceModel\StripeCustomer\CollectionFactory;
use StripeIntegration\Payments\Model\StripeCustomer;
use Vonnda\Checkout\Model\AffirmSubscriptionFactory;
use Vonnda\Checkout\Model\ResourceModel\AffirmSubscription;

class Affirmflow extends Action
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

    /** @var CollectionFactory */
    protected $stripeCustomerCollectionFactory;

    /** @var OrderRepositoryInterface */
    protected $orderRepo;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var AffirmSubscription */
    protected $resourceModel;

    /** @var AffirmSubscriptionFactory */
    protected $factory;

    /** @var Json */
    protected $jsonSerializer;

    public function __construct(
            Context $context,
            PageFactory $resultPageFactory,
            Config $config,
            Generic $helper,
            StripeCustomer $stripeCustomer,
            CollectionFactory $stripeCustomerCollectionFactory,
            OrderRepositoryInterface $orderRepo,
            SearchCriteriaBuilder $searchCriteriaBuilder,
            JsonFactory $resultJsonFactory,
            AffirmSubscription $resourceModel,
            AffirmSubscriptionFactory $factory,
            Json $jsonSerializer
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->config = $config;
        $this->helper = $helper;
        $this->stripeCustomer = $stripeCustomer;
        $this->stripeCustomerCollectionFactory = $stripeCustomerCollectionFactory;
        $this->orderRepo = $orderRepo;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->resourceModel = $resourceModel;
        $this->factory = $factory;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Execute action based on request and return result
     * Note: Request will be added as operation argument in future
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        $result = $this->resultJsonFactory->create();
        try {
            if (empty($params['payment']) || empty($params['payment']['cc_stripejs_token'])) {
                throw new Exception("Sorry, the card could not be saved. Unable to use Stripe.js.");
            }
            $parts = explode(":", $params['payment']['cc_stripejs_token']);
            if (!$this->helper->isValidToken($parts[0])) {
                throw new Exception("Sorry, the card could not be saved. Unable to use Stripe.js.");
            }
            try {
                $order = $this->getOrder($params['order_id']);
                if (!$order->getCustomerIsGuest() && $order->getCustomerId()) {
                    /** @var \StripeIntegration\Payments\Model\ResourceModel\StripeCustomer\Collection $collection */
                    $collection = $this->stripeCustomerCollectionFactory->create();
                    $collection->addFieldToFilter('customer_id', $order->getCustomerId());

                    /** @var StripeCustomer $stripeCustomer */
                    $stripeCustomer = $collection->getFirstItem();
                }
                if ($stripeCustomer && $stripeCustomer->getId()) {
                    $this->stripeCustomer = $stripeCustomer;
                } else {
                    $this->stripeCustomer->createStripeCustomer($order);
                }
                $card = $this->stripeCustomer->addCard($parts[0]);
                if (!$card) {
                    throw new Exception("Sorry, the card could not be saved.");
                }

                /** @var \Vonnda\Checkout\Model\AffirmSubscription $model */
                $model = $this->factory->create();
                $model->setOrderId($params['order_id']);
                $model->setStripeId($card->id);
                $model->setStripeCustomer($this->stripeCustomer->getId());
                $model->setAddress($this->jsonSerializer->serialize($params['address']));
                $this->resourceModel->save($model);

                // Because sometimes it is returned as a nested object
                if (!$card->exp_month) {
                    $expirationDate = $card->card->exp_month."/".$card->card->exp_year;
                    $cardString = $card->card->brand." ".$card->card->last4;
                } else {
                    $expirationDate = $card->exp_month."/".$card->exp_year;
                    $cardString = $card->brand." ".$card->last4;
                }
                $response = [
                        'status' => 'success',
                        'payment_code' => $card->id,
                        'stripe_customer_id' => $this->stripeCustomer->getId(),
                        'expiration_date' => $expirationDate,
                        'card_string' => $cardString
                ];
                $result->setData($response);
            } catch (Exception $e) {
                $this->helper->logError($e->getMessage());
                $response = [
                        'status' => 'error',
                        'message' => $e->getMessage()
                ];
                return $result->setData($response);
            }
        } catch (Card $e) {
            $result->setData(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (Error $e) {
            $this->helper->logError($e->getMessage());
            $this->helper->logError($e->getTraceAsString());
            $response = [
                    'status' => 'error',
                    'message' => 'Sorry, the card could not be saved.',
                    'critical_error' => $e->getMessage()
            ];
            return $result->setData($response);
        } catch (Exception $e) {
            $result->setData(['status' => 'error', 'message' => $e->getMessage()]);
            $this->helper->logError($e->getMessage());
            $this->helper->logError($e->getTraceAsString());
        }
        return $result;
    }

    public function getOrder($idOrIncrementId)
    {
        try {
            return $this->orderRepo->get($idOrIncrementId);
        } catch (NoSuchEntityException $exception) {
            // if the order is not found with id, try with increment id
            $searchCriteria = $this->searchCriteriaBuilder->addFilter('increment_id', $idOrIncrementId)->create();
            $orderList = $this->orderRepo->getList($searchCriteria);
            if ($orderList->getTotalCount()) {
                foreach ($orderList->getItems() as $order) {
                    if ($order->getIncrementId() == $idOrIncrementId) {
                        return $order;
                    }
                }
            }
        }
        return null;
    }
}