<?php

namespace Swissup\Marketplace\Job;

use Swissup\Marketplace\Api\JobInterface;

class MaintenanceEnable extends AbstractJob implements JobInterface
{
    protected $status = true;

    public function __construct(
        \Magento\Framework\App\MaintenanceMode $maintenanceMode
    ) {
        $this->maintenanceMode = $maintenanceMode;
    }

    public function execute()
    {
        $this->maintenanceMode->set($this->status);
    }
}
