<?php

namespace Swissup\Marketplace\Model\Handler\Additional;

use Swissup\Marketplace\Api\HandlerInterface;

class ProductionDisable extends ProcessRunner implements HandlerInterface
{
    protected $command = 'bin/magento deploy:mode:set developer';

    public function getTitle()
    {
        return __('Disable Production Mode');
    }
}
