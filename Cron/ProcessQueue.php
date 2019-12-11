<?php

namespace Swissup\Marketplace\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Stdlib\DateTime;
use Swissup\Marketplace\Model\Job;

class ProcessQueue
{
    /**
     * @var \Magento\Framework\App\Cache\Manager
     */
    private $cacheManager;

    /**
     * @var \Magento\Framework\App\MaintenanceMode
     */
    private $maintenanceMode;

    /**
     * @var WriteInterface
     */
    private $write;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @var \Swissup\Marketplace\Helper\Data
     */
    private $helper;

    /**
     * @var \Swissup\Marketplace\Model\ResourceModel\Job\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \Swissup\Marketplace\Service\JobDispatcher
     */
    private $dispatcher;

    /**
     * @param \Magento\Framework\App\Cache\Manager $cacheManager
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\App\MaintenanceMode $maintenanceMode
     * @param \Magento\Framework\Filesystem\Directory\WriteFactory $writeFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Swissup\Marketplace\Helper\Data $helper
     * @param \Swissup\Marketplace\Model\ResourceModel\Job\CollectionFactory $collectionFactory
     * @param \Swissup\Marketplace\Service\JobDispatcher $dispatcher
     */
    public function __construct(
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\App\MaintenanceMode $maintenanceMode,
        \Magento\Framework\Filesystem\Directory\WriteFactory $writeFactory,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Swissup\Marketplace\Helper\Data $helper,
        \Swissup\Marketplace\Model\ResourceModel\Job\CollectionFactory $collectionFactory,
        \Swissup\Marketplace\Service\JobDispatcher $dispatcher
    ) {
        $this->cacheManager = $cacheManager;
        $this->directoryList = $directoryList;
        $this->maintenanceMode = $maintenanceMode;
        $this->write = $writeFactory->create(BP);
        $this->jsonSerializer = $jsonSerializer;
        $this->helper = $helper;
        $this->collectionFactory = $collectionFactory;
        $this->dispatcher = $dispatcher;
    }

    public function execute()
    {
        if (!$this->helper->canUseAsyncMode()) {
            return;
        }

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

                $this->dispatcher->dispatchNow(
                    $job->getClass(),
                    $this->jsonSerializer->unserialize($job->getArgumentsSerialized())
                );

                $job->setStatus(Job::STATUS_SUCCESS);
            } catch (\Exception $e) {
                $job->setStatus(Job::STATUS_ERRORED)
                    ->setOutput($e->getMessage());
            } finally {
                $job->setFinishedAt($this->getCurrentDate())
                    ->save();
            }
        }

        // Don't use GeneratedFiles::requestRegeneration 'cos is has a
        // race condition bug that leads to disabled cache
        try {
            $this->cleanGeneratedFiles();
        } catch (\Exception $e) {
            //
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
     * @return void
     */
    private function cleanGeneratedFiles()
    {
        $cacheTypes = [];
        foreach ($this->cacheManager->getStatus() as $type => $status) {
            if (!$status) {
                continue;
            }
            $cacheTypes[] = $type;
        }

        $paths = [
            $this->write->getRelativePath($this->directoryList->getPath(DirectoryList::CACHE)),
            $this->write->getRelativePath($this->directoryList->getPath(DirectoryList::GENERATED_CODE)),
            $this->write->getRelativePath($this->directoryList->getPath(DirectoryList::GENERATED_METADATA)),
        ];

        foreach ($paths as $path) {
            if ($this->write->isDirectory($path)) {
                $this->write->delete($path);
            }
        }

        if ($cacheTypes) {
            $this->cacheManager->setEnabled($cacheTypes, true);
        }
    }
}
