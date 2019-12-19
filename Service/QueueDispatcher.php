<?php

namespace Swissup\Marketplace\Service;

use Magento\Framework\Stdlib\DateTime;
use Swissup\Marketplace\Model\Handler\Wrapper;
use Swissup\Marketplace\Model\Job;
use Swissup\Marketplace\Model\ResourceModel\Job\Collection;

class QueueDispatcher
{
    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Swissup\Marketplace\Model\JobFactory $jobFactory
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Swissup\Marketplace\Model\JobFactory $jobFactory
    ) {
        $this->objectManager = $objectManager;
        $this->jsonSerializer = $jsonSerializer;
        $this->jobFactory = $jobFactory;
    }

    /**
     * @param Collection $collection
     * @return void
     */
    public function dispatch(Collection $collection)
    {
        if (!$collection->count()) {
            return;
        }

        $queue = $this->prepareQueue($collection);

        foreach ($queue as $job) {
            try {
                $job->setStatus(Job::STATUS_RUNNING)
                    ->setStartedAt($this->getCurrentDate())
                    ->setAttempts($job->getAttempts() + 1)
                    ->save();

                $output = $job->getHandler()->execute();

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
    }

    /**
     * @param Collection $collection
     * @return array
     */
    private function prepareQueue(Collection $collection)
    {
        $queue = $collection->getItems();

        foreach ($queue as $job) {
            $job->reset()->setStatus(Job::STATUS_QUEUED)->save();
        }

        $createdAt = $collection->getFirstItem()->getCreatedAt();
        $createdAt = (new \DateTime($createdAt))->modify('-1 second');
        $preProcess = $this->createJob([
            'class' => Wrapper::class,
            'created_at' => $createdAt->format(DateTime::DATETIME_PHP_FORMAT),
        ]);

        $postProcess = $this->createJob(Wrapper::class);

        foreach ($queue as $job) {
            $handler = $this->createHandler($job);

            $job->setHandler($handler);

            $preProcess->getHandler()->addTasks($handler->beforeQueue());
            $postProcess->getHandler()->addTasks($handler->afterQueue());
        }

        array_unshift($queue, $preProcess);
        array_push($queue, $postProcess);

        return $queue;
    }

    private function createJob($data)
    {
        $defaults = [
            'arguments_serialized' => '{}',
            'created_at' => $this->getCurrentDate(),
            'status' => Job::STATUS_QUEUED,
        ];

        if (is_string($data)) {
            $data = ['class' => $data];
        }

        $data = array_merge($defaults, $data);

        $job = $this->jobFactory->create()->addData($data)->save();
        $job->setHandler($this->createHandler($job));

        return $job;
    }

    /**
     * @param Job $job
     * @return HandlerInterface
     */
    private function createHandler(Job $job)
    {
        $arguments = $job->getArgumentsSerialized();
        $arguments = $arguments ? $this->jsonSerializer->unserialize($arguments) : [];
        return $this->objectManager->create($job->getClass(), $arguments);
    }

    /**
     * @return string
     */
    private function getCurrentDate()
    {
        return (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT);
    }
}
