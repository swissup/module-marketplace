<?php

namespace Swissup\Marketplace\Model\Handler\Additional;

use Swissup\Marketplace\Model\Handler\AbstractHandler;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process as SymfonyProcess;

class ProcessRunner extends AbstractHandler
{
    /**
     * \Swissup\Marketplace\Model\Process
     */
    private $process;

    /**
     * @var string|null
     */
    protected $command;

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
        return $this->process->run($this->getCommand(), $this->getProcessCallback());
    }

    /**
     * @return callable|\Psr\Log\LoggerInterface
     */
    protected function getProcessCallback()
    {
        // getOutput() is not used on purpose - it creates a BufferedOutput,
        // that is never displayed to the user.
        $output = $this->output;

        if (!$output instanceof ConsoleOutputInterface) {
            return $this->getLogger();
        }

        return function ($type, $buffer) use ($output) {
            $stream = $type === SymfonyProcess::ERR ? $output->getErrorOutput() : $output;

            $stream->write($buffer, false, OutputInterface::OUTPUT_RAW);
        };
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
