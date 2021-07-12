<?php

namespace Swissup\Marketplace\Installer\Command;

use Swissup\Marketplace\Installer\Request;
use Swissup\Marketplace\Model\Traits\LoggerAware;

class CmsPage
{
    use LoggerAware;

    /**
     * @var \Magento\Cms\Model\PageFactory
     */
    private $pageFactory;

    /**
     * @var \Magento\Cms\Model\ResourceModel\Page\CollectionFactory
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
     * @var \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory
     */
    private $urlRewriteCollectionFactory;

    /**
     * @param \Magento\Cms\Model\PageFactory $pageFactory
     * @param \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Cms\Model\PageFactory $pageFactory,
        \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $collectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory $urlRewriteCollectionFactory
    ) {
        $this->pageFactory = $pageFactory;
        $this->collectionFactory = $collectionFactory;
        $this->localeDate = $localeDate;
        $this->storeManager = $storeManager;
        $this->urlRewriteCollectionFactory = $urlRewriteCollectionFactory;
    }

    /**
     * @param Request $request
     * @return void
     */
    public function execute(Request $request)
    {
        $identifiers = array_map(
            function ($item) {
                return $item['identifier'];
            },
            $request->getParams()
        );

        $this->cleanupUrlRewrites($identifiers, $request->getStoreIds());

        $isSingleStoreMode = $this->storeManager->isSingleStoreMode();
        $collection = $this->collectionFactory->create()
            ->addStoreFilter($request->getStoreIds())
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('identifier', ['in' => $identifiers]);

        $this->logger->info('Cms Pages: Backup existing pages');
        foreach ($collection as $page) {
            $page->load($page->getId()); // load stores

            $storesToLeave = array_diff($page->getStoreId(), $request->getStoreIds());

            if (count($storesToLeave) && !$isSingleStoreMode) {
                $page->setStores($storesToLeave);
            } else {
                // duplicate page, because original page will be used for new content
                $page = $this->pageFactory->create()
                    ->addData($page->getData())
                    ->unsPageId()
                    ->setIsActive(0)
                    ->setIdentifier($this->getBackupIdentifier($page->getIdentifier()));
            }

            try {
                $page->save();
            } catch (\Exception $e) {
                $this->logger->warning(
                    sprintf('%s "%s"', $e->getMessage(), $page->getIdentifier())
                );
            }
        }

        $this->logger->info('CMS PAGES: Create new pages');
        foreach ($request->getParams() as $data) {
            $canUseExistingPage = false;
            $pages = $collection->getItemsByColumnValue(
                'identifier',
                $data['identifier']
            );

            // If page is linked to destination stores only - use it. Otherwise, create new.
            foreach ($pages as $page) {
                $diff = array_diff($page->getStoreId(), $request->getStoreIds());
                if (!count($diff)) {
                    $canUseExistingPage = true;
                    break;
                }
            }

            if (!$canUseExistingPage) {
                $page = $this->pageFactory->create();
            }

            $data = array_merge([
                'is_active'         => 1,
                'page_layout'       => '1column',
                'content_heading'   => '',
                'layout_update_xml' => '',
                'custom_theme'      => null,
                'custom_root_template' => null,
                'custom_layout_update_xml' => null,
            ], $data);

            try {
                $page->addData($data)
                    ->setStores($request->getStoreIds()) // see Magento\Cms\Model\ResourceModel\Page::_afterSave
                    ->save();
            } catch (\Exception $e) {
                $this->logger->warning(
                    sprintf('%s "%s"', $e->getMessage(), $data['identifier'])
                );
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

    /**
     * @param  array $identifiers
     * @return void
     */
    private function cleanupUrlRewrites($identifiers, $storeIds)
    {
        $urls = $this->urlRewriteCollectionFactory->create()
            ->addFieldToFilter('entity_type', 'cms-page')
            ->addFieldToFilter('request_path', ['in' => $identifiers]);

        if (in_array(0, $storeIds)) {
            $storeIds = array_keys($this->storeManager->getStores(true));
        }

        $urls->addFieldToFilter('store_id', ['in' => $storeIds]);

        foreach ($urls as $url) {
            $url->delete();
        }
    }
}
