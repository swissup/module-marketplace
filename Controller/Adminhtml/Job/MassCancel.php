<?php

namespace Swissup\Marketplace\Controller\Adminhtml\Job;

use Magento\Backend\App\Action\Context;
use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory as CronCollectionFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Swissup\Marketplace\Model\ResourceModel\Job\CollectionFactory as JobCollectionFactory;

class MassCancel extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Marketplace::package_manage';

    /**
     * MassActions filter
     *
     * @var Filter
     */
    protected $filter;

    /**
     * @var CronCollectionFactory
     */
    protected $cronCollectionFactory;

    /**
     * @var JobCollectionFactory
     */
    protected $jobCollectionFactory;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CronCollectionFactory $cronCollectionFactory
     * @param JobCollectionFactory $jobCollectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CronCollectionFactory $cronCollectionFactory,
        JobCollectionFactory $jobCollectionFactory
    ) {
        $this->filter = $filter;
        $this->cronCollectionFactory = $cronCollectionFactory;
        $this->jobCollectionFactory = $jobCollectionFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $jobs = $this->filter->getCollection($this->jobCollectionFactory->create());
        $scheduleIds = $jobs->getColumnValues('cron_schedule_id');

        if ($scheduleIds) {
            $cron = $this->cronCollectionFactory->create()
                ->addFieldToFilter('job_code', 'swissup_marketplace_job_run')
                ->addFieldToFilter('schedule_id', $scheduleIds);
        }

        foreach ($jobs as $item) {
            $item->cancel();

            if ($id = $item->getCronScheduleId()) {
                $cronItem = $cron->getItemById($id);

                if (!$cronItem) {
                    continue;
                }

                $cronItem
                    ->setStatus(Schedule::STATUS_SUCCESS)
                    ->save();
            }
        }

        $this->messageManager->addSuccess(
            __('A total of %1 record(s) have been updated.', $jobs->getSize())
        );

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/package/');
    }
}
