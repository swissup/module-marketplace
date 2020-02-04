<?php

namespace Swissup\Marketplace\Model\Logger;

class BufferLogger extends \Psr\Log\AbstractLogger
{
    protected $log = [];

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        $this->log[] = sprintf("[%s] %s", $level, $message);
    }

    public function getLog()
    {
        return $this->log;
    }
}
