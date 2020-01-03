<?php

namespace Swissup\Marketplace\Model;

use Symfony\Component\Process\Process as SymfonyProcess;

class Process
{
    /**
     * string
     */
    private $phpPath;

    /**
     * @param \Magento\Framework\Process\PhpExecutableFinderFactory $phpExecutableFinderFactory
     */
    public function __construct(
        \Magento\Framework\Process\PhpExecutableFinderFactory $phpExecutableFinderFactory
    ) {
        $this->phpPath = $phpExecutableFinderFactory->create()->find() ?: 'php';
    }

    /**
     * @param string $command
     * @param mixed $callback
     * @return string
     * @throws \Exception
     */
    public function run($command, $callback = null)
    {
        $command = explode(' ', $command);

        array_unshift($command, $this->phpPath);

        if ($callback &&
            !is_callable($callback) &&
            $callback instanceof \Psr\Log\LoggerInterface
        ) {
            $logger = $callback;
            $callback = function ($type, $buffer) use ($logger) {
                $buffer = trim($buffer, "\n\r ");

                if (!strlen($buffer)) {
                    return;
                }

                if ($type === SymfonyProcess::ERR) {
                    $logger->error($buffer);
                } else {
                    $logger->info($buffer);
                }
            };
        }

        return (new SymfonyProcess($command))
            ->setWorkingDirectory(BP)
            ->setTimeout(null)
            ->mustRun($callback)
            ->getOutput();
    }
}
