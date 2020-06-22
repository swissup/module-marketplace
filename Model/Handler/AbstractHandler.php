<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Model\Traits\CmdOptions;
use Swissup\Marketplace\Model\Traits\LoggerAware;
use Swissup\Marketplace\Model\Traits\OutputAware;

class AbstractHandler extends \Magento\Framework\DataObject
{
    use CmdOptions, LoggerAware, OutputAware;

    public function handle()
    {
        $this->getLogger()->info($this->getTitle());

        return $this->execute();
    }

    public function execute()
    {
        throw new \Exception('Execute method is not implemented');
    }

    public function getTitle()
    {
        return get_class($this);
    }

    public function validateBeforeHandle()
    {
        return true;
    }

    public function validateBeforeDispatch()
    {
        return true;
    }

    public function beforeQueue()
    {
        return [];
    }

    public function afterQueue()
    {
        return [];
    }
}
