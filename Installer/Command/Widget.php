<?php

namespace Swissup\Marketplace\Installer\Command;

use Magento\Widget\Model\Widget\Instance;
use Swissup\Marketplace\Installer\Request;
use Swissup\Marketplace\Model\Traits\LoggerAware;

class Widget
{
    use LoggerAware;

    /**
     * @var \Magento\Widget\Model\Widget\InstanceFactory
     */
    private $widgetFactory;

    /**
     * @param \Magento\Widget\Model\Widget\InstanceFactory $widgetFactory
     */
    public function __construct(
        \Magento\Widget\Model\Widget\InstanceFactory $widgetFactory
    ) {
        $this->widgetFactory = $widgetFactory;
    }

    /**
     * @param Request $request
     * @return void
     */
    public function execute(Request $request)
    {
        $this->logger->info('Widget: Create new widgets');

        foreach ($request->getParams() as $raw) {
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
