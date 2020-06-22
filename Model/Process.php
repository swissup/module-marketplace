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
     * \Swissup\Marketplace\Model\PackagesList\Local
     */
    private $packages;

    /**
     * @param \Magento\Framework\Process\PhpExecutableFinderFactory $phpExecutableFinderFactory
     * @param \Swissup\Marketplace\Model\PackagesList\Local $packages
     */
    public function __construct(
        \Magento\Framework\Process\PhpExecutableFinderFactory $phpExecutableFinderFactory,
        \Swissup\Marketplace\Model\PackagesList\Local $packages
    ) {
        $this->phpPath = $phpExecutableFinderFactory->create()->find() ?: 'php';
        $this->packages = $packages;
    }

    /**
     * @param string $command
     * @param mixed $callback
     * @param boolean $php
     * @return string
     * @throws \Exception
     */
    public function run($command, $callback = null, $php = true)
    {
        $command = explode(' ', $command);

        if ($php) {
            array_unshift($command, $this->phpPath);
        }

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

        $packages = $this->packages->getList();
        if (!empty($packages['symfony/process']['version'])) {
            $version = $packages['symfony/process']['version'];
            $version = str_replace('v', '', $version);
            if (version_compare($version, '4.2', '<')) {
                $command = implode(' ', $command);
            }
        }

        return (new SymfonyProcess($command))
            ->setWorkingDirectory(BP)
            ->setTimeout(null)
            ->mustRun($callback)
            ->getOutput();
    }
}
