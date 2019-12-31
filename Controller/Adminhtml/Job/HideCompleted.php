<?php

namespace Swissup\Marketplace\Controller\Adminhtml\Job;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Swissup\Marketplace\Model\Job;
use Swissup\Marketplace\Model\ResourceModel\Job\CollectionFactory as JobCollectionFactory;

class HideCompleted extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Marketplace::package_manage';

    /**
     * @var JobCollectionFactory
     */
    protected $jobCollectionFactory;

    /**
     * @param Context $context
     * @param JobCollectionFactory $jobCollectionFactory
     */
    public function __construct(
        Context $context,
        JobCollectionFactory $jobCollectionFactory
    ) {
        $this->jobCollectionFactory = $jobCollectionFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response = new \Magento\Framework\DataObject();
        $ids = $this->getRequest()->getPostValue('ids');

        if ($ids) {
            $jobs = $this->jobCollectionFactory->create()
                ->addFieldToFilter('job_id', ['in' => $ids])
                ->addFieldToFilter('finished_at', ['notnull' => true]);

            foreach ($jobs as $job) {
                $job->setVisibility(Job::VISIBILITY_INVISIBLE_IN_ACTIVITY)
                    ->save();
            }

            $response->addData(['success' => true]);
        }

        return $resultJson->setData($response);
    }
}
