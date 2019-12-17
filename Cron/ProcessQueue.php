<?php

namespace Swissup\Marketplace\Cron;

use Magento\Framework\Stdlib\DateTime;
use Swissup\Marketplace\Model\Job;

class ProcessQueue
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
     * @var \Swissup\Marketplace\Service\JobDispatcher
     */
    private $dispatcher;

    /**
     * @param \Magento\Framework\App\MaintenanceMode $maintenanceMode
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Swissup\Marketplace\Helper\Data $helper
     * @param \Swissup\Marketplace\Model\JobFactory $jobFactory
     * @param \Swissup\Marketplace\Model\ResourceModel\Job\CollectionFactory $collectionFactory
     * @param \Swissup\Marketplace\Service\JobDispatcher $dispatcher
     */
    public function __construct(
        \Magento\Framework\App\MaintenanceMode $maintenanceMode,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Swissup\Marketplace\Helper\Data $helper,
        \Swissup\Marketplace\Model\JobFactory $jobFactory,
        \Swissup\Marketplace\Model\ResourceModel\Job\CollectionFactory $collectionFactory,
        \Swissup\Marketplace\Service\JobDispatcher $dispatcher
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
        if (!$this->helper->canUseAsyncMode()) {
            return;
        }

        $jobs = $this->getJobsToRun();
        if (!$jobs->count()) {
            return;
        }

        // prevent overlapping with next cronjob
        foreach ($jobs as $job) {
            $job->reset()->setStatus(Job::STATUS_QUEUED)->save();
        }

        $this->maintenanceMode->set(true);

        foreach ($jobs as $job) {
            try {
                $job->setStatus(Job::STATUS_RUNNING)
                    ->setStartedAt($this->getCurrentDate())
                    ->setAttempts($job->getAttempts() + 1)
                    ->save();

                $class = $job->getClass();
                $params = $job->getArgumentsSerialized() ?
                    $this->jsonSerializer->unserialize($job->getArgumentsSerialized()) : [];

                $output = $this->dispatcher->dispatchNow($class, $params);

                $job->setStatus(Job::STATUS_SUCCESS)
                    ->setOutput((string) $output);
            } catch (\Exception $e) {
                $job->setStatus(Job::STATUS_ERRORED)
                    ->setOutput($e->getMessage());
            } finally {
                $job->setFinishedAt($this->getCurrentDate())
                    ->save();
            }
        }

        $this->maintenanceMode->set(false);
    }

    /**
     * @return string
     */
    private function getCurrentDate()
    {
        return (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT);
    }

    /**
     * @return \Swissup\Marketplace\Model\ResourceModel\Job\Collection
     */
    private function getJobsToRun()
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
            ->setOrder('created_at', 'ASC')
            ->setPageSize(20);

        if (!$jobs->count()) {
            return $jobs;
        }

        // manually add post-jobs
        $extraJobs = [
            \Swissup\Marketplace\Job\CleanGeneratedFiles::class,
            \Swissup\Marketplace\Job\SetupUpgrade::class,
        ];
        foreach ($extraJobs as $className) {
            $job = $this->jobFactory->create()
                ->addData([
                    'class' => $className,
                    'arguments_serialized' => '{}',
                    'created_at' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
                    'status' => Job::STATUS_PENDING,
                ])
                ->save();

            $jobs->addItem($job);
        }

        return $jobs;
    }
}
