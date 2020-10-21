<?php

namespace Swissup\Marketplace\Model\Handler\Additional;

use Psr\Log\LoggerInterface;
use Swissup\Marketplace\Api\HandlerInterface;
use Swissup\Marketplace\Model\Logger\NoErrorsConsoleLogger;
use Symfony\Component\Console\Logger\ConsoleLogger;

class SetupUpgrade extends ProcessRunner implements HandlerInterface
{
    protected $command = 'bin/magento setup:upgrade';

    public function getTitle()
    {
        return __('Run setup:upgrade');
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        // suppress errors created in
        // \Magento\ComposerRootUpdatePlugin\Utils\Console::log
        if ($logger instanceof ConsoleLogger && $this->getOutput()) {
            $logger = new NoErrorsConsoleLogger($this->getOutput());
        }

        return parent::setLogger($logger);
    }
}
