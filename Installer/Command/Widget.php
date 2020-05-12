<?php

namespace Swissup\Marketplace\Installer\Command;

use Magento\Widget\Model\Widget\Instance;
use Swissup\Marketplace\Installer\Request;
use Swissup\Marketplace\Model\Traits\LoggerAware;

class Widget
{
    use LoggerAware;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Widget\Model\Widget\InstanceFactory
     */
    private $widgetFactory;

    /**
     * @var \Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory $collectionFactory
     */
    private $collectionFactory;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Widget\Model\Widget\InstanceFactory $widgetFactory
     * @param \Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Widget\Model\Widget\InstanceFactory $widgetFactory,
        \Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory $collectionFactory
    ) {
        $this->storeManager = $storeManager;
        $this->widgetFactory = $widgetFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param Request $request
     * @return void
     */
    public function execute(Request $request)
    {
        $this->logger->info('Widgets: Backup existing and create new widgets');

        $isSingleStoreMode = $this->storeManager->isSingleStoreMode();

        foreach ($request->getParams() as $raw) {
            $collection = $this->collectionFactory->create()
                ->addStoreFilter($request->getStoreIds())
                ->addFieldToFilter('title', $raw['title'])
                ->addFieldToFilter('instance_type', $raw['type'])
                ->addFieldToFilter('theme_id', $raw['theme_id']);

            foreach ($collection as $widget) {
                $storesToLeave = array_diff($widget->getStoreIds(), $request->getStoreIds());

                if (count($storesToLeave) && !$isSingleStoreMode) {
                    // unset stores. new widget will be added for them.
                    $widget->setStoreIds($storesToLeave);
                } else {
                    // widget already exists
                    continue 2;
                }

                try {
                    $widget->save();
                } catch (\Exception $e) {
                    $this->logger->warning($e->getMessage());
                }
            }

            $data = [
                'title' => $raw['title'],
                'instance_type' => $raw['type'],
                'theme_id' => $raw['theme_id'],
                'store_ids' => $request->getStoreIds(),
                'widget_parameters' => $raw['params'],
                'sort_order' => $raw['sort_order'] ?? 0,
            ];

            $pageGroups = [];
            foreach ($raw['pages'] as $page) {
                $pageGroup = $this->getPageGroupData($page, [
                    'page_id' => 0,
                    'for' => 'all',
                    'block' => $page['reference'] ?? 'content.top',
                    'template' => $raw['template'] ?? '',
                ]);

                if ($pageGroup) {
                    $pageGroups[] = $pageGroup;
                }
            }

            $data['page_groups'] = $pageGroups;

            try {
                $this->widgetFactory->create()->addData($data)->save();
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage());
            }
        }
    }

    private function getPageGroupData($data, $defaultData)
    {
        if (isset($data['handle'])) {
            $groupName = $data['handle'] === 'default' ? 'all_pages' : 'pages';
            $groupData = [
                'layout_handle' => $data['handle'],
            ];
        } elseif (isset($data['page_layout'])) {
            $groupName = 'page_layouts';
            $groupData = [
                'layout_handle' => $data['page_layout'],
            ];
        } elseif (isset($data['category_ids'])) {
            $groupName = 'anchor_categories';
            $groupData = [
                'for' => Instance::SPECIFIC_ENTITIES,
                'entities' => $data['category_ids'],
            ];
        } elseif (isset($data['product_ids'])) {
            $groupName = 'all_products';
            $groupData = [
                'for' => Instance::SPECIFIC_ENTITIES,
                'entities' => $data['product_ids'],
            ];
        }

        if ($groupName && $groupData) {
            return [
                'page_group' => $groupName,
                $groupName => array_merge($defaultData, $groupData),
            ];
        }

        return false;
    }
}
