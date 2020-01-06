<?php

namespace Swissup\Marketplace\Model\Handler\Additional;

use Swissup\Marketplace\Api\HandlerInterface;

class ProductionEnable extends ProcessRunner implements HandlerInterface
{
    protected $command = 'bin/magento deploy:mode:set production';

    public function getTitle()
    {
        return __('Enable Production Mode');
    }
}
