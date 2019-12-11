<?php

namespace Swissup\Marketplace\Model\Config\Source;

class JobStatus implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Swissup\Marketplace\Model\Job
     */
    private $job;

    /**
     * @param \Swissup\Marketplace\Model\Job $job
     */
    public function __construct(
        \Swissup\Marketplace\Model\Job $job
    ) {
        $this->job = $job;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result = [];
        foreach ($this->toArray() as $value => $label) {
            $result[] = [
                'value' => $value,
                'label' => $label,
            ];
        }
        return $result;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return $this->job->getAvailableStatuses();
    }
}
