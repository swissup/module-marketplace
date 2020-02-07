<?php

namespace Swissup\Marketplace\Cron;

use Magento\Framework\Stdlib\DateTime;
use Swissup\Marketplace\Model\Job;

class QueueProcess
{
    /**
     * @var \Magento\Framework\App\MaintenanceMode
     */
    private $maintenanceMode;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @var \Swissup\Marketplace\Helper\Data
     */
    private $helper;

    /**
     * @var \Swissup\Marketplace\Model\JobFactory $jobFactory
     */
    private $jobFactory;

    /**
     * @var \Swissup\Marketplace\Model\ResourceModel\Job\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \Swissup\Marketplace\Service\QueueDispatcher
     */
    private $dispatcher;

    /**
     * @param \Magento\Framework\App\MaintenanceMode $maintenanceMode
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Swissup\Marketplace\Helper\Data $helper
     * @param \Swissup\Marketplace\Model\JobFactory $jobFactory
     * @param \Swissup\Marketplace\Model\ResourceModel\Job\CollectionFactory $collectionFactory
     * @param \Swissup\Marketplace\Service\QueueDispatcher $dispatcher
     */
    public function __construct(
        \Magento\Framework\App\MaintenanceMode $maintenanceMode,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Swissup\Marketplace\Helper\Data $helper,
        \Swissup\Marketplace\Model\JobFactory $jobFactory,
        \Swissup\Marketplace\Model\ResourceModel\Job\CollectionFactory $collectionFactory,
        \Swissup\Marketplace\Service\QueueDispatcher $dispatcher
    ) {
        $this->maintenanceMode = $maintenanceMode;
        $this->jsonSerializer = $jsonSerializer;
        $this->helper = $helper;
        $this->jobFactory = $jobFactory;
        $this->collectionFactory = $collectionFactory;
        $this->dispatcher = $dispatcher;
    }

    public function execute()
    {
        $jobs = $this->collectionFactory->create()
            ->addFieldToFilter('status', Job::STATUS_PENDING)
            ->addFieldToFilter('scheduled_at', [
                'or' => [
                    ['date' => true, 'to' => $this->getCurrentDate()],
                    ['is' => new \Zend_Db_Expr('null')],
                ]
            ])
            ->setOrder('scheduled_at', 'ASC')
            ->setOrder('created_at', 'ASC');

        $latest = $jobs->getLastItem();
        if ($latest->getId()) {
            $createdAt = new \DateTime($latest->getCreatedAt());
            $compareWith = new \DateTime();

            if ($createdAt > $compareWith) {
                return;
            }
        }

        $this->dispatcher->dispatch($jobs);
    }

    /**
     * @return string
     */
    private function getCurrentDate()
    {
        return (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT);
    }
}
