<?php

namespace Swissup\Marketplace\Model\Installer\Commands;

use Swissup\Marketplace\Model\Installer\Request;

class CmsBlock
{
    /**
     * @var \Magento\Cms\Model\BlockFactory
     */
    private $blockFactory;

    /**
     * @var \Magento\Cms\Model\ResourceModel\Block\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $localeDate;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Magento\Cms\Model\BlockFactory $blockFactory
     * @param \Magento\Cms\Model\ResourceModel\Block\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Cms\Model\BlockFactory $blockFactory,
        \Magento\Cms\Model\ResourceModel\Block\CollectionFactory $collectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->blockFactory = $blockFactory;
        $this->collectionFactory = $collectionFactory;
        $this->localeDate = $localeDate;
        $this->storeManager = $storeManager;
    }

    /**
     * @param Request $request
     * @return void
     */
    public function execute(Request $request)
    {
        $isSingleStoreMode = $this->storeManager->isSingleStoreMode();

        foreach ($request->getParams() as $data) {
            // 1. backup existing blocks
            $collection = $this->collectionFactory->create()
                ->addStoreFilter($request->getStoreIds())
                ->addFieldToFilter('identifier', $data['identifier']);

            foreach ($collection as $block) {
                $block->load($block->getId()); // load stores

                $storesToLeave = array_diff($block->getStoreId(), $request->getStoreIds());

                if (count($storesToLeave) && !$isSingleStoreMode) {
                    $block->setStores($storesToLeave);
                } else {
                    $block->setIsActive(0)
                        ->setIdentifier($this->getBackupIdentifier($block->getIdentifier()));
                }

                try {
                    $block->save();
                } catch (\Exception $e) {
                    // todo
                }
            }

            $data = array_merge([
                'is_active' => 1,
            ], $data);

            // 2. create new block
            try {
                $this->blockFactory->create()
                    ->setData($data)
                    ->setStores($request->getStoreIds()) // see Magento\Cms\Model\ResourceModel\Block::_afterSave
                    ->save();
            } catch (\Exception $e) {
                // todo
            }
        }
    }

    /**
     * @param string $identifier
     * @return string
     */
    private function getBackupIdentifier($identifier)
    {
        return $identifier
            . '_backup_'
            . rand(10, 99)
            . '_'
            . $this->localeDate->date()->format('Y-m-d-H-i-s');
    }
}
