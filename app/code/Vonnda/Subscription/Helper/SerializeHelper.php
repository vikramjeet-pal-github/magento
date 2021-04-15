<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Helper;

use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Model\SubscriptionHistoryRepository;
use Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\App\Helper\AbstractHelper;

class SerializeHelper extends AbstractHelper
{
    /**
     * Subscription Customer Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionCustomerRepository $subscriptionCustomerRepository
     */
    protected $subscriptionCustomerRepository;

    protected $subscriptionHistoryRepository;

    protected $dataObjectProcessor;


    public function __construct(
        SubscriptionHistoryRepository $subscriptionHistoryRepository,
        DataObjectProcessor $dataObjectProcessor
    ) {
        $this->subscriptionHistoryRepository = $subscriptionHistoryRepository;
        $this->dataObjectProcessor = $dataObjectProcessor;
    }

    public function serializeSubscriptionCustomer($subscriptionCustomer)
    {
        if($subscriptionCustomer){
            $dataArray = $this->dataObjectProcessor->buildOutputDataArray($subscriptionCustomer, SubscriptionCustomerInterface::class);
            return serialize($dataArray);    
        }
        return '';
    }

    public function getAbbreviatedChanges($subscriptionHistoryId)
    {
        $subscriptionHistory = $this->subscriptionHistoryRepository->getById($subscriptionHistoryId);
        $beforeSaveData = unserialize($subscriptionHistory->getBeforeSave());
        $afterSaveData = unserialize($subscriptionHistory->getAfterSave());

        $changed = [];
        foreach($beforeSaveData as $key => $value){
            if(in_array($key, ['updated_at', 'coupon_codes'])){
                continue;
            }
            if((isset($beforeSaveData[$key]) && !isset($afterSaveData[$key])) || (!isset($beforeSaveData[$key]) && isset($afterSaveData[$key]))){
                $changed[] = $key;
                continue;
            }
            if((is_array($beforeSaveData[$key]) && !is_array($afterSaveData[$key])) || (!is_array($beforeSaveData[$key]) && is_array($afterSaveData[$key]))){
                if(!$this->deepCompare($beforeSaveData[$key], $afterSaveData[$key])){
                    $changed[] = $key;
                    continue;
                }
            } 
            if(is_array($beforeSaveData[$key]) && is_array($afterSaveData[$key])){
                if(!$this->deepCompare($beforeSaveData[$key], $afterSaveData[$key])){
                    $changed[] = $key;
                    continue;
                }
            } 
            if($beforeSaveData[$key] != $afterSaveData[$key]){
                $changed[] = $key;
                continue;
            }
        }

        return $this->formatOutput($changed);
    }

    protected function deepCompare($array1, $array2)
    {
        foreach($array1 as $key => $value){
            if(!empty($array1[$key]) && !empty($array2[$key]) && is_array($array1[$key]) && is_array($array2[$key])){
                return $this->deepCompare($array1[$key], $array2[$key]);
            } 
            if(isset($array1[$key]) && isset($array2[$key]) && $array1[$key] != $array2[$key]){
                return false;
            }
        }
        return true;
    }

    protected function formatOutput($changed)
    {
        $output = '';
        foreach($changed as $index => $key){
            if($index === 0){
                $output .= "Updated: ";
            } else {
                $output .= ", ";
            }
            $output .= ucwords(str_replace('_', ' ', $key));
        } 
        return $output;
    }
}
