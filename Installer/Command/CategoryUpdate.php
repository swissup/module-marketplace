<?php

namespace Swissup\Marketplace\Installer\Command;

use Magento\Store\Model\Store;
use Swissup\Marketplace\Installer\Request;
use Swissup\Marketplace\Model\Traits\LoggerAware;

class CategoryUpdate
{
    use LoggerAware;

    /**
     * @var \Swissup\Marketplace\Installer\Helper\Collection
     */
    private $collectionHelper;

    public function __construct(
        \Swissup\Marketplace\Installer\Helper\Collection $collectionHelper
    ) {
        $this->collectionHelper = $collectionHelper;
    }

    /**
     * @param Request $request
     * @return void
     */
    public function execute(Request $request)
    {
        $this->logger->info('Category Update: Prepare category data');

        foreach ($request->getParams() as $data) {
            $collection = $this->collectionHelper->getCollection(
                [],
                \Magento\Catalog\Model\ResourceModel\Category\Collection::class,
                $data['filters'] ?? []
            );

            $storeIds = $data['store_id'] ?? [Store::DEFAULT_STORE_ID];
            if (!is_array($storeIds)) {
                $storeIds = [$storeIds];
            }

            foreach ($collection as $category) {
                foreach ($data['data'] as $key => $value) {
                    $category
                        ->setData($key, $value)
                        ->setCustomAttribute($key, $value);
                }

                foreach ($storeIds as $storeId) {
                    try {
                        $category->setStoreId($storeId)->save();
                    } catch (\Exception $e) {
                        $this->logger->warning($e->getMessage());
                    }
                }
            }
        }
    }
}
