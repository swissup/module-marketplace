<?php

namespace Swissup\Marketplace\Model\Handler;

class AbstractHandler extends \Magento\Framework\DataObject
{
    private $logger;

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

    public function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new \Psr\Log\NullLogger();
        }
        return $this->logger;
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger = null)
    {
        $this->logger = $logger;

        return $this;
    }

    public function validate()
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
