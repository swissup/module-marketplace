<?php

namespace Swissup\Marketplace\Model\Handler\Additional;

use Swissup\Marketplace\Model\Handler\AbstractHandler;

class ProcessRunner extends AbstractHandler
{
    /**
     * \Swissup\Marketplace\Model\Process
     */
    private $process;

    /**
     * @var string|null
     */
    private $command;

    /**
     * @param \Swissup\Marketplace\Model\Process $process
     * @param array $data
     */
    public function __construct(
        \Swissup\Marketplace\Model\Process $process,
        array $data = []
    ) {
        $this->process = $process;
        parent::__construct($data);
    }

    /**
     * @return string
     */
    public function execute()
    {
        return $this->process->run($this->getCommand(), $this->getLogger());
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getCommand()
    {
        if (!$this->command) {
            throw new \Exception("Command is not defined");
        }
        return $this->command;
    }
}
