<?php

namespace Swissup\Marketplace\Cron;

use Magento\Framework\Stdlib\DateTime;

class CleanupCache
{
    /**
     * @var \Swissup\Marketplace\Model\Cache
     */
    private $cache;

    /**
     * @param \Swissup\Marketplace\Model\Cache $cache
     */
    public function __construct(
        \Swissup\Marketplace\Model\Cache $cache
    ) {
        $this->cache = $cache;
    }

    public function execute()
    {
        $this->cache->clean();
    }
}
