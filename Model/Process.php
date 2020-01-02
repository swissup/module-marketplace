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

    public function run($command)
    {
        $command = explode(' ', $command);

        array_unshift($command, $this->phpPath);

        return (new SymfonyProcess($command))
            ->setWorkingDirectory(BP)
            ->setTimeout(600)
            ->mustRun()
            ->getOutput();
    }
}
