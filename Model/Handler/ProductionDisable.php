<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Api\HandlerInterface;

class ProductionDisable extends AbstractHandler implements HandlerInterface
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
        return __('Disable Production Mode');
    }

    /**
     * @return string
     */
    public function execute()
    {
        return $this->process->run('bin/magento deploy:mode:set developer', $this->getLogger());
    }
}
