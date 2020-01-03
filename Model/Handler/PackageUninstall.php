<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Api\HandlerInterface;

class PackageUninstall extends PackageAbstractHandler implements HandlerInterface
{
    public function execute()
    {
        return $this->packageManager->uninstall($this->packages);
    }

    public function validate()
    {
        return $this->validateWhenDisable();
    }

    public function getTitle()
    {
        return __('Uninstall %1', implode(', ', $this->packages));
    }

    /**
     * @return array
     */
    public function beforeQueue()
    {
        return [
            MaintenanceEnable::class => true,
            ProductionDisable::class => $this->isProduction(),
        ];
    }

    /**
     * @return array
     */
    public function afterQueue()
    {
        return [
            CleanupFilesystem::class => true,
            SetupUpgrade::class => true,
            ProductionEnable::class => $this->isProduction(),
            MaintenanceDisable::class => true,
        ];
    }
}
