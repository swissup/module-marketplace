<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Api\HandlerInterface;
use Symfony\Component\Process\Process;

class ProductionEnable extends AbstractHandler implements HandlerInterface
{
    /**
     * \Symfony\Component\Process\PhpExecutableFinder
     */
    private $phpExecutableFinder;

    /**
     * @param \Magento\Framework\Process\PhpExecutableFinderFactory $phpExecutableFinderFactory
     */
    public function __construct(
        \Magento\Framework\Process\PhpExecutableFinderFactory $phpExecutableFinderFactory
    ) {
        $this->phpExecutableFinder = $phpExecutableFinderFactory->create();
    }

    public function getTitle()
    {
        return __('Enable Production Mode');
    }

    /**
     * @return string
     */
    public function execute()
    {
        $phpPath = $this->phpExecutableFinder->find() ?: 'php';

        $process = (new Process([$phpPath, 'bin/magento', 'deploy:mode:set', 'production']))
            ->setWorkingDirectory(BP)
            ->setTimeout(600);

        return $process->mustRun()->getOutput();
    }
}
