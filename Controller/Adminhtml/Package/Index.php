<?php

namespace Swissup\Marketplace\Controller\Adminhtml\Package;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Marketplace::package_index';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory
     */
    protected $cronCollectionFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory $cronCollectionFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory $cronCollectionFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->cronCollectionFactory = $cronCollectionFactory;
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $this->validateCron();

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Swissup_Marketplace::package_index');
        $resultPage->addBreadcrumb(__('Marketplace'), __('Marketplace'));
        $resultPage->getConfig()->getTitle()->prepend(__('Package Manager'));
        return $resultPage;
    }

    protected function validateCron()
    {
        $jobs = $this->cronCollectionFactory->create()
            ->addFieldToFilter('job_code', 'indexer_reindex_all_invalid')
            ->addFieldToFilter(
                'created_at',
                [
                    'gt' => (new \DateTime('-1 hour'))->format(DateTime::DATETIME_PHP_FORMAT)
                ]
            )
            ->addFieldToFilter('executed_at', ['notnull' => true])
            ->setPageSize(1);

        if (!$jobs->count()) {
            $this->messageManager->addError(__(
                "Cron is not configured properly. Marketplace won't work without cron."
            ));
        }
    }
}
