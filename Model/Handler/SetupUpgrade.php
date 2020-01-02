<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Api\HandlerInterface;

class SetupUpgrade extends AbstractHandler implements HandlerInterface
{
    /**
     * \Swissup\Marketplace\Model\Process
     */
    private $process;

    /**
     * @param \Swissup\Marketplace\Model\Process $process
     */
    public function __construct(
        \Swissup\Marketplace\Model\Process $process
    ) {
        $this->process = $process;
    }

    public function getTitle()
    {
        return __('Run setup:upgrade');
    }

    /**
     * @return string
     */
    public function execute()
    {
        $this->process->run('bin/magento setup:upgrade');
    }
}
