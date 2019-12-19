<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Api\HandlerInterface;

class Wrapper extends AbstractHandler implements HandlerInterface
{
    /**
     * @var array
     */
    private $tasks = [];

    /**
     * @param \Swissup\Marketplace\Service\JobDispatcher $dispatcher
     */
    public function __construct(
        \Swissup\Marketplace\Service\JobDispatcher $dispatcher
    ) {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param array $tasks
     */
    public function addTasks(array $tasks = [])
    {
        foreach ($tasks as $job) {
            $this->tasks[$job] = $job;
        }
    }

    public function execute()
    {
        $output = [];

        foreach ($this->tasks as $task) {
            $output[] = $this->dispatcher->dispatchNow($task);
        }

        return implode("\n\n", array_filter($output));
    }
}
