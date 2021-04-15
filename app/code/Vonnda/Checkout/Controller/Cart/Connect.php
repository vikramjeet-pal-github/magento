<?php

namespace Vonnda\Checkout\Controller\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;   
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\GuestCartRepositoryInterface;

class Connect extends \Magento\Framework\App\Action\Action
{

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var Cart */
    protected $cart;

    /** @var Session */
    protected $checkoutSession;

    /** @var CustomerSession */
    protected $customerSession;

    /** @var GuestCartRepositoryInterface */
    protected $guestCartRepository;

    /**
     * @param Context $context
     * @param Cart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param Session $checkoutSession
     * @param CustomerSession $customerSession
     * @param GuestCartRepositoryInterface $guestCartRepository
     */
    public function __construct(
        Context $context,
        Cart $cart,
        ProductRepositoryInterface $productRepository,
        Session $checkoutSession,
        CustomerSession $customerSession,
        GuestCartRepositoryInterface $guestCartRepository
    ) {
        $this->cart = $cart;
        $this->productRepository = $productRepository;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->guestCartRepository = $guestCartRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $params = $this->getRequest()->getParams();
            if(array_key_exists('cartId', $params)){
                $cartId = $params['cartId'];
                $cart = $this->guestCartRepository->get($cartId);
                if (!$cart) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Unable to connect to cart as the provided cart id is not valid.'));
                }
                $this->checkoutSession->setQuoteId($cart->getId());
            } else {
                $params = $this->mapParams($params);
                $this->cart->getQuote()->removeAllItems();
                foreach ($params as $key => $param) {
                    if (isset($param['qty'])) {
                        $filter = new \Zend_Filter_LocalizedToNormalized(
                            ['locale' => $this->_objectManager->get(
                                \Magento\Framework\Locale\ResolverInterface::class
                            )->getLocale()]
                        );
                        $params[$key]['qty'] = $filter->filter($param['qty']);
                    }
                }
                foreach ($params as $key => $param) {
                    $product = $this->_initProduct($param);
                    if ($product->getTypeId() == "bundle") {
                        $bundleOptions = $this->getBundleOptions($product);
                        $param = [
                            'product' => $product->getId(),
                            'bundle_option' => $bundleOptions,
                            'qty' => $param['qty']
                        ];
                    }
                    $this->cart->addProduct($product, $param);
                    $this->cart->save();
                    if (!$this->checkoutSession->getNoCartRedirect(true)) {
                        if (!$this->cart->getQuote()->getHasError()) {
                            $this->messageManager->addComplexSuccessMessage(
                                'addCartSuccessMessage',
                                [
                                    'product_name' => $product->getName(),
                                    'cart_url' => $this->getCartUrl()
                                ]
                            );
                        }
                    }
                }    
            }
            if (!$this->cart->getQuote()->getHasError()) {
                $resultRedirect->setUrl($this->getCheckoutUrl());
                return $resultRedirect;
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if ($this->checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNoticeMessage(
                    $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addErrorMessage(
                        $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($message)
                    );
                }
            }
            $url = $this->checkoutSession->getRedirectUrl(true);
            if (!$url) {
                $url = $this->_redirect->getRedirectUrl($this->getCartUrl());
            }
            $resultRedirect->setUrl($url);
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t add this item to your shopping cart right now.')
            );
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);

            $resultRedirect->setUrl($this->getCartUrl());

            return $resultRedirect;
        }
    }

    /**
     * Initialize product instance from request data
     * @param array $param
     * @return \Magento\Catalog\Model\Product|array|false
     */
    protected function _initProduct($param)
    {
        $productSku = $param['sku'];

        if ($productSku) {
            $storeId = $this->_objectManager->get(
                \Magento\Store\Model\StoreManagerInterface::class
            )->getStore()->getId();
            try {
                return $this->productRepository->get($productSku, false, $storeId, true);
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }

    private function mapParams($params)
    {
        $mappedParams = [];
        foreach ($params as $sku => $qty) {
            $mappedParams[] = [
                "sku" => $sku,
                "qty" => $qty
            ];
        }

        return $mappedParams;
    }

    /**
     * @param $product
     * @return mixed
     */
    private function getBundleOptions(Product $product)
    {
        $selectionCollection = $product->getTypeInstance()
            ->getSelectionsCollection(
                $product->getTypeInstance()->getOptionsIds($product),
                $product
            );
        $bundleOptions = [];
        foreach ($selectionCollection as $selection) {
            if ($selection->getData("is_default") == "1") {
                $bundleOptions[$selection->getOptionId()][] = $selection->getSelectionId();
            }
        }
        return $bundleOptions;
    }

    /**
     * Retrieve checkout URL
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getCheckoutUrl()
    {
        return $this->_url->getUrl('checkout');
    }

    /**
     * Returns cart url
     *
     * @return string
     */
    private function getCartUrl()
    {
        return $this->_url->getUrl('checkout/cart', ['_secure' => true]);
    }
}
