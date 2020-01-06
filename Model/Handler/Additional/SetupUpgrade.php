<?php

namespace Swissup\Marketplace\Model\Handler\Additional;

use Swissup\Marketplace\Api\HandlerInterface;

class SetupUpgrade extends ProcessRunner implements HandlerInterface
{
    protected $command = 'bin/magento setup:upgrade';

    public function getTitle()
    {
        return __('Run setup:upgrade');
    }
}
