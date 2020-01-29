<?php

namespace Swissup\Marketplace\Model\Config\Source;

use Magento\Framework\Escaper;
use Magento\Store\Model\System\Store as SystemStore;

class StoresTree implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Escaper
     *
     * @var Escaper
     */
    protected $escaper;

    /**
     * System store
     *
     * @var SystemStore
     */
    protected $systemStore;

    /**
     * Constructor
     *
     * @param SystemStore $systemStore
     * @param Escaper $escaper
     */
    public function __construct(SystemStore $systemStore, Escaper $escaper)
    {
        $this->systemStore = $systemStore;
        $this->escaper = $escaper;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $websiteCollection = $this->systemStore->getWebsiteCollection();
        $groupCollection = $this->systemStore->getGroupCollection();
        $storeCollection = $this->systemStore->getStoreCollection();

        $result[0] = [
            'label' => __('All Store Views'),
            'value' => 0,
            'optgroup' => []
        ];

        foreach ($websiteCollection as $website) {
            $groups = [];
            foreach ($groupCollection as $group) {
                if ($group->getWebsiteId() != $website->getId()) {
                    continue;
                }

                $stores = [];
                foreach ($storeCollection as $store) {
                    if ($store->getGroupId() != $group->getId()) {
                        continue;
                    }
                    $stores[] = [
                        'label' => $this->escaper->escapeHtml($store->getName()),
                        'value' => $store->getId(),
                    ];
                }

                if (!empty($stores)) {
                    $groups[] = [
                        'label' => $this->escaper->escapeHtml($group->getName()),
                        'value' => 'store_group_' . $group->getId(),
                        'optgroup' => array_values($stores),
                    ];
                }
            }

            if (!empty($groups)) {
                $result[0]['optgroup'][] = [
                    'label' => $this->escaper->escapeHtml($website->getName()),
                    'value' => 'website_' . $website->getId(),
                    'optgroup' => array_values($groups),
                ];
            }
        }

        return $result;
    }
}
