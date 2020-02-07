<?php

namespace Swissup\Marketplace\Installer;

use Magento\Store\Model\Store;

class Request extends \Magento\Framework\DataObject
{
    public function getStoreIds()
    {
        return $this->_data['store_id'] ?? [Store::DEFAULT_STORE_ID];
    }
}
