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

namespace Vonnda\DeviceManager\Api\Data;

interface DeviceManagerSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get DeviceManager list.
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface[]
     */
    public function getItems();

    /**
     * Set entity_id list.
     * @param \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
