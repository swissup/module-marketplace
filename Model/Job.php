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

    /**
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_RUNNING => __('Running'),
            self::STATUS_SUCCESS => __('Success'),
            self::STATUS_SKIPPED => __('Skipped'),
            self::STATUS_ERRORED => __('Errored'),
        ];
    }
}
