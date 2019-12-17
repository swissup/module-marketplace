<?php

namespace Swissup\Marketplace\Ui\DataProvider;

use Magento\Framework\Stdlib\DateTime;
use Swissup\Marketplace\Model\ResourceModel\Job\CollectionFactory;

class JobActivityDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * Construct
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collection
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collection,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);

        $this->collection = $collection->create()
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
            ->setOrder('job_id', 'DESC');
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $date = new \DateTime();

        return array_merge($this->getCollection()->toArray(), [
            'secondsToNextQueue' => 60 - $date->format('s'),
            'time' => $date->format(DateTime::DATETIME_PHP_FORMAT),
        ]);
    }
}
