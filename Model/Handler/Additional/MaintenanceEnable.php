<?php

namespace Swissup\Marketplace\Model\Handler\Additional;

use Swissup\Marketplace\Api\HandlerInterface;
use Swissup\Marketplace\Model\Handler\AbstractHandler;

class MaintenanceEnable extends AbstractHandler implements HandlerInterface
{
    protected $status = true;

    protected $maintenanceMode;

    public function __construct(
        \Magento\Framework\App\MaintenanceMode $maintenanceMode,
        array $data = []
    ) {
        $this->maintenanceMode = $maintenanceMode;
        parent::__construct($data);
    }

    public function getTitle()
    {
        return __('Enable Maintenance Mode');
    }

    public function execute()
    {
        try {
            $this->maintenanceMode->set($this->status);
            $this->maintenanceMode->setAddresses('');
        } catch (\Exception $e) {
            throw new \Exception("Error when enabling maintenance mode: " . $e->getMessage());
        }
    }
}
