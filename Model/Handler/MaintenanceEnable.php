<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Api\HandlerInterface;

class MaintenanceEnable extends AbstractHandler implements HandlerInterface
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
