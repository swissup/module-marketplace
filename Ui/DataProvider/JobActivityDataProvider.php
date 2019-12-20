<?php

namespace Swissup\Marketplace\Ui\DataProvider;

use Magento\Framework\Stdlib\DateTime;

class JobActivityDataProvider extends JobDataProvider
{
    /**
     * @return Swissup\Marketplace\Model\ResourceModel\Job\Collection
     */
    public function getCollection()
    {
        $this->collection
            ->addFieldToFilter('created_at', [
                'date' => true,
                'from' => (new \DateTime('-3 hours'))->format(DateTime::DATETIME_PHP_FORMAT),
            ])
            ->addFieldToFilter('finished_at', [
                'or' => [
                    [
                        'date' => true,
                        'from' => (new \DateTime('-3 minutes'))->format(DateTime::DATETIME_PHP_FORMAT)
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
        $date = new \DateTime();

        return array_merge(parent::getData(), [
            'secondsToNextQueue' => 60 - $date->format('s'),
            'time' => $date->format(DateTime::DATETIME_PHP_FORMAT),
        ]);
    }
}
