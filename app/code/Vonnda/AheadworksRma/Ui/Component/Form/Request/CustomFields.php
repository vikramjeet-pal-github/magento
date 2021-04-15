<?php
namespace Vonnda\AheadworksRma\Ui\Component\Form\Request;

use Aheadworks\Rma\Api\CustomFieldRepositoryInterface;
use Aheadworks\Rma\Api\Data\CustomFieldInterface;
use Aheadworks\Rma\Api\Data\RequestInterface;
use Aheadworks\Rma\Api\RequestRepositoryInterface;
use Aheadworks\Rma\Model\CustomField\Renderer\Backend\Mapper;
use Aheadworks\Rma\Model\Source\CustomField\EditAt;
use Aheadworks\Rma\Model\Source\CustomField\Refers;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Container;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Element\ActionDelete;

/**
 * Completely replacing class Aheadworks\Rma\Ui\Component\Form\Request\CustomFields
 * because they made everything private except prepare
 * @package Vonnda\AheadworksRma\Ui\Component\Form\Request
 */
class CustomFields extends Container
{

    /** @var UiComponentFactory */
    protected $uiComponentFactory;

    /** @var CustomFieldRepositoryInterface */
    protected $customFieldRepository;

    /** @var RequestRepositoryInterface */
    protected $requestRepository;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var Mapper */
    protected $mapper;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CustomFieldRepositoryInterface $customFieldRepository
     * @param RequestRepositoryInterface $requestRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StoreManagerInterface $storeManager
     * @param Mapper $mapper
     * @param UiComponentInterface[] $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CustomFieldRepositoryInterface $customFieldRepository,
        RequestRepositoryInterface $requestRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager,
        Mapper $mapper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->uiComponentFactory = $uiComponentFactory;
        $this->customFieldRepository = $customFieldRepository;
        $this->requestRepository = $requestRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
        $this->mapper = $mapper;
    }

    /** {@inheritdoc} */
    public function prepare()
    {
        $status = $this->getRequestStatus();
        $refersTo = $this->getData('config/refersTo') ? : Refers::REQUEST;
        $addActionDelete = $this->getData('config/addActionDelete');
        foreach ($this->getCustomFields($refersTo, $status) as $customField) {
            $config = $this->mapper->map($customField, $status);
            $this->createComponent(
                $this->getCustomFieldName($customField),
                Field::NAME,
                $config
            );
        }
        if ($refersTo == 'item') {
            $this->createComponent(
                'rma_location',
                Field::NAME,
                [
                    'required' => true,
                    'label' => 'RMA Location',
                    'options' => [
                        ['value' => '', 'label' => 'Please select'],
                        ['value' => 'alom', 'label' => 'ALOM'],
                        ['value' => 'godirect_canada', 'label' => 'GoDirect - Canada'],
                        ['value' => 'grs', 'label' => 'GRS'],
                        ['value' => 'peco_zero', 'label' => 'PECO Zero']
                    ],
                    'default_options' => [],
                    'dataType' => 'number',
                    'formElement' => 'select',
                    'component' => 'Aheadworks_Rma/js/ui/form/element/select',
                    'disabled' => ($status > 0 ? true : false),
                    'validation' => ['required-entry' => true],
                    'columnsHeaderClasses' => ['required' => true],
                    'visible' => true
                ]
            );
        }
        if ($addActionDelete) {
            $this->createComponent(
                'action_delete',
                ActionDelete::NAME,
                $this->getActionDeleteConfig()
            );
        }
        parent::prepare();
    }

    /**
     * @param CustomFieldInterface $customField
     * @return string
     */
    protected function getCustomFieldName($customField)
    {
        return 'custom_fields' . '.' . $customField->getId();
    }

    /**
     * @param string $refersTo
     * @param int $status
     * @return CustomFieldInterface[]
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getCustomFields($refersTo, $status)
    {
        $this->searchCriteriaBuilder
            ->addFilter(CustomFieldInterface::REFERS, $refersTo)
            ->addFilter(CustomFieldInterface::OPTIONS, 'enabled');
        if ($status != EditAt::NEW_REQUEST_PAGE) {
            $requestStoreId = $this->getRequest()->getStoreId();
            $websiteId = $this->storeManager->getStore($requestStoreId)->getWebsiteId();
            $this->searchCriteriaBuilder->addFilter(CustomFieldInterface::WEBSITE_IDS, $websiteId);
        }
        return $this->customFieldRepository
            ->getList($this->searchCriteriaBuilder->create())
            ->getItems();
    }

    /**
     * @param string $fieldName
     * @param string $type
     * @param array $config
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function createComponent($fieldName, $type, $config)
    {
        $component = $this->uiComponentFactory->create($fieldName, $type, ['context' => $this->getContext()]);
        $component->setData('config', $config);
        $component->prepare();
        $this->addComponent($fieldName, $component);
        return $this;
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     */
    protected function getRequestStatus()
    {
        $request = $this->getRequest();
        return !empty($request) ? $request->getStatusId() : EditAt::NEW_REQUEST_PAGE;
    }

    /**
     * @return RequestInterface|null
     * @throws NoSuchEntityException
     */
    protected function getRequest()
    {
        $id = $this->getContext()->getRequestParam(
            $this->getContext()->getDataProvider()->getRequestFieldName()
        );
        return !empty($id) ? $this->requestRepository->get($id) : null;
    }

    /**
     * @return array
     */
    protected function getActionDeleteConfig()
    {
        return [
            'componentType' => 'actionDelete',
            'component' => 'Aheadworks_Rma/js/ui/dynamic-rows/action-delete',
            'dataType' => 'text',
            'label' => __('Actions'),
            'template' => 'Magento_Backend/dynamic-rows/cells/action-delete',
            'imports' => [
                'visible' => '${ $.provider }:data.newRequest'
            ],
            'additionalClasses' => [
                'control-table-options-cell' => true
            ],
            'columnsHeaderClasses' => [
                'control-table-options-th' => true
            ]
        ];
    }

}