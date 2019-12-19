<?php

namespace Swissup\Marketplace\Cron;

use Magento\Framework\Stdlib\DateTime;

class QueueCleanup
{
    /**
     * @var \Swissup\Marketplace\Model\ResourceModel\Job\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param \Swissup\Marketplace\Model\ResourceModel\Job\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Swissup\Marketplace\Model\ResourceModel\Job\CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Removes records older that 1 week.
     * Skips 50 records.
     */
    public function execute()
    {
        $date = (new \DateTime('-1 week'))->format(DateTime::DATETIME_PHP_FORMAT);
        $jobs = $this->collectionFactory->create()
            ->addFieldToFilter('scheduled_at', [
                'or' => [
                    ['date' => true, 'to' => $date],
                    ['is' => new \Zend_Db_Expr('null')],
                ]
            ])
            ->addFieldToFilter('created_at', [
                ['date' => true, 'to' => $date]
            ])
            ->setOrder('created_at', 'ASC')
            ->setPageSize(250);

        $itemsToRemove = $jobs->count() - 50;

        foreach ($jobs as $job) {
            if ($itemsToRemove-- < 0) {
                break;
            }

            $job->delete();
        }
    }
}
