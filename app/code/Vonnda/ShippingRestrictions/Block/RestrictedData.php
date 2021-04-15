<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\ShippingRestrictions\Block;

use Magento\Framework\Profiler;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Html\Select;
use Vonnda\ShippingRestrictions\Model\Shipping\Restrictions;
use Magento\Directory\Block\Data as CoreDirectoryBlock;

class RestrictedData extends CoreDirectoryBlock
{

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    protected $shippingRestrictions;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        Restrictions $shippingRestrictions,
        SerializerInterface $serializer,
        array $data = []
    ) {
        $this->shippingRestrictions = $shippingRestrictions;
        $this->serializer = $serializer;
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $data
        );
    }

    /**
     * @param null|string $defValue
     * @param string $name
     * @param string $id
     * @param string $title
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRestrictedCountryHtmlSelect($defValue = null, $name = 'country_id', $id = 'country', $title = 'Country')
    {
        if ($defValue === null) {
            $defValue = $this->getCountryId();
        }
        $options = $this->shippingRestrictions->getAllowedShippingCountriesOptionArray();
        return $this->getLayout()->createBlock(\Magento\Framework\View\Element\Html\Select::class)->setName($name)->setId($id)->setTitle(__($title))
            ->setValue($defValue)->setOptions($options)->setExtraParams('data-validate="{\'validate-select\':true}"')->getHtml();
    }

    /**
     * Returns region html select
     *
     * @param null|int $value
     * @param string $name
     * @param string $id
     * @param string $title
     * @return string
     */
    public function getRegionSelect(
            ?int $value = null,
            string $name = 'region',
            string $id = 'state',
            string $title = 'State/Province'
    ): string {
        Profiler::start('TEST: '.__METHOD__, ['group' => 'TEST', 'method' => __METHOD__]);
        if ($value === null) {
            $value = (int) $this->getRegionId();
        }
        $cacheKey = 'DIRECTORY_REGION_SELECT_STORE'.$this->_storeManager->getStore()->getId();
        $cache = $this->_configCacheType->load($cacheKey);
        if ($cache) {
            $options = $this->serializer->unserialize($cache);
        } else {
            $options = $this->getRegionCollection()->toOptionArray();
            $this->_configCacheType->save($this->serializer->serialize($options), $cacheKey);
        }
        $disallowedStates = [
                'American Samoa',
                'Federated States Of Micronesia',
                'Marshall Islands',
                'Northern Mariana Islands',
                'Palau'
        ];
        foreach ($options as $key => $option) {
            if (in_array($option['label'], $disallowedStates)) {
                unset($options[$key]);
            }
        }
        $html = $this->getLayout()->createBlock(Select::class)->setName($name)->setTitle(__($title))->setId($id)->setClass('required-entry validate-state')
                     ->setValue($value)->setOptions($options)->getHtml();
        Profiler::start('TEST: '.__METHOD__, ['group' => 'TEST', 'method' => __METHOD__]);
        return $html;
    }

}