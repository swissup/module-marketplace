<?php

namespace Swissup\Marketplace\Ui\DataProvider;

use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory as CronCollectionFactory;
use Magento\Framework\Stdlib\DateTime;
use Swissup\Marketplace\Model\HandlerFactory;
use Swissup\Marketplace\Model\Job;
use Swissup\Marketplace\Model\ResourceModel\Job\Collection;

class JobActivityDataProvider extends JobDataProvider
{
    /**
     * @var CronCollectionFactory
     */
    protected $cronCollectionFactory;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Collection $collection
     * @param HandlerFactory $handlerFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Collection $collection,
        HandlerFactory $handlerFactory,
        CronCollectionFactory $cronCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $collection,
            $handlerFactory,
            $meta,
            $data
        );

        $this->cronCollectionFactory = $cronCollectionFactory;
    }

    /**
     * @return Swissup\Marketplace\Model\ResourceModel\Job\Collection
     */
    public function getCollection()
    {
        $this->collection
            ->addFieldToFilter('visibility', Job::VISIBILITY_VISIBLE)
            ->addFieldToFilter('created_at', [
                'date' => true,
                'from' => (new \DateTime('-3 hours'))->format(DateTime::DATETIME_PHP_FORMAT),
            ])
            ->addFieldToFilter('finished_at', [
                'or' => [
                    [
                        'date' => true,
                        'from' => (new \DateTime('-10 minutes'))->format(DateTime::DATETIME_PHP_FORMAT)
                    ],
                    [
                        'is' => new \Zend_Db_Expr('null')
                    ],
                ]
            ])
            ->setOrder('created_at', 'DESC')
            ->setOrder('job_id', 'DESC');

        return parent::getCollection();
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        return array_merge(parent::getData(), [
            'secondsToNextQueue' => $this->getSecondsToNextCronRun(),
        ]);
    }

    /**
     * Try to calculate seconds to the next cron job.
     *
     * @return int
     */
    private function getSecondsToNextCronRun()
    {
        $date = new \DateTime();

        $items = $this->cronCollectionFactory->create()
            ->addFieldToFilter('job_code', 'indexer_reindex_all_invalid')
            ->addFieldToFilter('executed_at', ['notnull' => true])
            ->setOrder('executed_at', 'desc')
            ->setPageSize(2);

        if ($items->count() !== 2) {
            $offset = 0;
        } else {
            $lastRunTime = new \DateTime($items->getFirstItem()->getExecutedAt());
            $prevRunTime = new \DateTime($items->getLastItem()->getExecutedAt());
            $lastRunTime->setTime($lastRunTime->format('H'), $lastRunTime->format('i'), 0);
            $prevRunTime->setTime($prevRunTime->format('H'), $prevRunTime->format('i'), 0);
            $interval    = $prevRunTime->diff($lastRunTime);
            $nextRunTime = $lastRunTime->add($interval);
            $nextRunDiff = $nextRunTime->diff($date);

            if (!$nextRunDiff->invert) {
                return 0;
            }

            $minutes = $nextRunDiff->i;
            $seconds = $nextRunDiff->s;

            if ($minutes > 0 && $seconds === 0) {
                $minutes--;
            }

            $offset = $minutes * 60;
        }

        return $offset + 60 - $date->format('s');
    }
}
