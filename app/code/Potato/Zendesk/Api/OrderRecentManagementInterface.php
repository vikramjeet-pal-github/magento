<?php

namespace Potato\Zendesk\Api;

interface OrderRecentManagementInterface
{
    /**
     * @param string $email
     * @param integer|\Magento\Store\Model\Website|\Magento\Store\Model\Store $scope
     * @return array
     */
    public function getInfo($email, $scope);

    /**
     * @param string $incrementId
     * @param integer|\Magento\Store\Model\Website|\Magento\Store\Model\Store $scope
     * @return array
     */
    public function getInfoFromOrder($incrementId, $scope);
}
