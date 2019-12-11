<?php

namespace Swissup\Marketplace\Cron;

use Magento\Framework\Stdlib\DateTime;
use Swissup\Marketplace\Model\Job;

class ProcessQueue
{
    /**
     * @var \Magento\Framework\Code\GeneratedFiles
     */
    private $generatedFiles;

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
     * @param \Magento\Framework\Code\GeneratedFiles $generatedFiles
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Swissup\Marketplace\Helper\Data $helper
     * @param \Swissup\Marketplace\Model\ResourceModel\Job\CollectionFactory $collectionFactory
     * @param \Swissup\Marketplace\Service\JobDispatcher $dispatcher
     */
    public function __construct(
        \Magento\Framework\Code\GeneratedFiles $generatedFiles,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Swissup\Marketplace\Helper\Data $helper,
        \Swissup\Marketplace\Model\ResourceModel\Job\CollectionFactory $collectionFactory,
        \Swissup\Marketplace\Service\JobDispatcher $dispatcher
    ) {
        $this->generatedFiles = $generatedFiles;
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

        // @todo: enable maintenance mode

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

        $this->generatedFiles->requestRegeneration();

        // @todo disable maintenance mode
    }

    /**
     * @return string
     */
    private function getCurrentDate()
    {
        return (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT);
    }
}
