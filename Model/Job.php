<?php

namespace Swissup\Marketplace\Model;

use Magento\Framework\Stdlib\DateTime;

class Job extends \Magento\Framework\Model\AbstractModel
{
    const STATUS_PENDING = 0;
    const STATUS_QUEUED  = 1;
    const STATUS_RUNNING = 2;
    const STATUS_SUCCESS = 3;
    const STATUS_SKIPPED = 4;
    const STATUS_ERRORED = 5;
    const STATUS_CANCELED = 6;

    const VISIBILITY_VISIBLE = 1;
    const VISIBILITY_INVISIBLE = 2;
    const VISIBILITY_INVISIBLE_IN_ACTIVITY = 3;

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

    public function cancel()
    {
        $cancelable = [
            self::STATUS_PENDING,
            self::STATUS_QUEUED,
            self::STATUS_RUNNING,
        ];

        if (!in_array($this->getStatus(), $cancelable)) {
            return $this;
        }

        return $this->setStatus(self::STATUS_CANCELED)
            ->setFinishedAt((new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT))
            ->save();
    }

    /**
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_QUEUED  => __('Queued'),
            self::STATUS_RUNNING => __('Running'),
            self::STATUS_SUCCESS => __('Success'),
            self::STATUS_SKIPPED => __('Skipped'),
            self::STATUS_ERRORED => __('Errored'),
            self::STATUS_CANCELED => __('Canceled'),
        ];
    }
}
