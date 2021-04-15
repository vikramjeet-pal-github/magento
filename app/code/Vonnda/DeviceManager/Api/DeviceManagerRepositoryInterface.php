<?php
/**
 * A Magento 2 module named Vonnda/DeviceManager
 * Copyright (C) 2018  
 * 
 * This file is part of Vonnda/DeviceManager.
 * 
 * Vonnda/DeviceManager is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Vonnda\DeviceManager\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface DeviceManagerRepositoryInterface
{

    /**
     * Save DeviceManager
     * @param \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface $deviceManager
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface $deviceManager
    );

    /**
     * Retrieve DeviceManager
     * @param string $devicemanagerId
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($devicemanagerId);

    /**
     * Retrieve DeviceManager matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete DeviceManager
     * @param \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface $deviceManager
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface $deviceManager
    );

    /**
     * Delete DeviceManager by ID
     * @param string $devicemanagerId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($devicemanagerId);
}
