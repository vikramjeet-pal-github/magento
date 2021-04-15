<?php
namespace Vonnda\AheadworksRma\Controller\Adminhtml\CustomField;

/**
 * Overriding all methods due to some private methods. Extends original to maintain type
 * Actual changes made to prepareOptionLabels to correctly sanitize store label data.
 * Skipping store ids caused blank strings to come through which would break the save,
 * and after the save failed, the post data was sent back to the form, which then also
 * broke because it couldnt display the bad post data.
 * No overwrite added to correct form display errors, as correcting the post data
 * will prevent the problem from happening, and it would have required another complete
 * overwrite of the file due to private methods and variables.
 */
class PostDataProcessor extends \Aheadworks\Rma\Controller\Adminhtml\CustomField\PostDataProcessor
{

    /**
     * Prepare entity data for save
     * @param array $data
     * @return array
     */
    public function prepareEntityData($data)
    {
        if (empty($data['editable_admin_for_status_ids'])) {
            $data['editable_admin_for_status_ids'] = [];
        }
        if (empty($data['visible_for_status_ids'])) {
            $data['visible_for_status_ids'] = [];
        }
        if (empty($data['editable_for_status_ids'])) {
            $data['editable_for_status_ids'] = [];
        }
        $data = $this->prepareOptions($data);
        return $data;
    }

    /**
     * Prepare options
     * @param array $data
     * @return array
     */
    protected function prepareOptions($data)
    {
        $options = isset($data['options']) ? $data['options'] : [];
        foreach ($options as $key => $option) {
            if (isset($option['delete']) && $option['delete']) {
                unset($data['options'][$key]);
            }
            if (isset($option['store_labels']) && !empty($option['store_labels'])) {
                $data['options'][$key]['store_labels'] = $this->prepareOptionLabels($option['store_labels']);
            }
        }
        return $data;
    }

    /**
     * Prepare option labels
     * @param array $storeLabels
     * @return array
     */
    protected function prepareOptionLabels($storeLabels)
    {
        foreach ($storeLabels as $storeId => &$storeLabel) {
            if (is_array($storeLabel)) {
                $storeLabel['store_id'] = $storeId;
            } else {
                unset($storeLabels[$storeId]);
            }
        }
        return $storeLabels;
    }

}