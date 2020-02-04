<?php

namespace Swissup\Marketplace\Model\Traits;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

trait LoggerAware
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }
}
