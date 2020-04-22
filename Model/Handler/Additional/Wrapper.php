<?php

namespace Swissup\Marketplace\Model\Handler\Additional;

use Swissup\Marketplace\Api\HandlerInterface;
use Swissup\Marketplace\Model\Handler\AbstractHandler;

class Wrapper extends AbstractHandler implements HandlerInterface
{
    /**
     * @var array
     */
    private $tasks;

    /**
     * @var \Swissup\Marketplace\Model\HandlerFactory
     */
    private $handlerFactory;

    /**
     * @param array $tasks
     * @param \Swissup\Marketplace\Model\HandlerFactory $handlerFactory
     * @param array $data
     */
    public function __construct(
        array $tasks,
        \Swissup\Marketplace\Model\HandlerFactory $handlerFactory,
        array $data = []
    ) {
        $this->tasks = $tasks;
        $this->handlerFactory = $handlerFactory;
        parent::__construct($data);
    }

    public function getTitle()
    {
        $titles = [];

        foreach ($this->tasks as $task) {
            try {
                $titles[] = $this->createTask($task)->getTitle();
            } catch (\Exception $e) {
                $titles[] = $e->getMessage();
            }
        }

        return implode(', ', $titles);
    }

    public function handle()
    {
        return $this->execute();
    }

    public function execute()
    {
        $failed = false;
        $output = [];

        foreach ($this->tasks as $task) {
            try {
                $handler = $this->createTask($task);
                $handler->validateBeforeHandle();
                $output[] = $handler->handle();
            } catch (\Exception $e) {
                $output[] = $e->getMessage();
                $failed = true;
            }
        }

        $result = implode("\n", array_filter($output));

        if ($failed) {
            throw new \Exception($result);
        }

        return $result;
    }

    private function createTask($class)
    {
        return $this->handlerFactory
            ->create($class, ['data' => $this->getData()])
            ->setLogger($this->getLogger());
    }
}
