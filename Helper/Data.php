<?php

namespace Swissup\Marketplace\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    /**
     * @var string
     */
    const CONFIG_PATH_ASYNC_MODE = 'swissup_marketplace/general/async';

    /**
     * @return boolean
     */
    public function canUseAsyncMode()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_ASYNC_MODE);
    }
}
