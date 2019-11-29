<?php

namespace Swissup\Marketplace\Model;

class Job extends \Magento\Framework\Model\AbstractModel
{
    const STATUS_PENDING = 0;
    const STATUS_RUNNING = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_SKIPPED = 3;
    const STATUS_ERRORED = 4;

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Swissup\Marketplace\Model\ResourceModel\Job::class);
    }

    public function reset()
    {
        return $this
            ->setStatus(self::STATUS_PENDING)
            ->setOutput(null)
            ->setStartedAt(null)
            ->setFinishedAt(null);
    }
}
