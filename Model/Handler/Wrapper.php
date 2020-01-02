<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Api\HandlerInterface;

class Wrapper extends AbstractHandler implements HandlerInterface
{
    /**
     * @var array
     */
    private $tasks;

    /**
     * @var \Swissup\Marketplace\Service\JobDispatcher
     */
    private $dispatcher;

    /**
     * @var \Swissup\Marketplace\Model\HandlerFactory
     */
    private $handlerFactory;

    /**
     * @param array $tasks
     * @param \Swissup\Marketplace\Service\JobDispatcher $dispatcher
     * @param \Swissup\Marketplace\Model\HandlerFactory $handlerFactory
     */
    public function __construct(
        array $tasks,
        \Swissup\Marketplace\Service\JobDispatcher $dispatcher,
        \Swissup\Marketplace\Model\HandlerFactory $handlerFactory
    ) {
        $this->tasks = $tasks;
        $this->dispatcher = $dispatcher;
        $this->handlerFactory = $handlerFactory;
    }

    public function getTitle()
    {
        $titles = [];

        foreach ($this->tasks as $task) {
            try {
                $titles[] = $this->handlerFactory->create($task)->getTitle();
            } catch (\Exception $e) {
                $titles[] = $e->getMessage();
            }
        }

        return implode(', ', $titles);
    }

    public function execute()
    {
        $failed = false;
        $output = [];

        foreach ($this->tasks as $task) {
            try {
                $output[] = $this->dispatcher->dispatchNow($task);
            } catch (\Exception $e) {
                $output[] = $e->getMessage();
                $failed = true;
            }
        }

        $result = implode("\n\n", array_filter($output));

        if ($failed) {
            throw new \Exception($result);
        }

        return $result;
    }
}
