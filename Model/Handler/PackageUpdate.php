<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Api\HandlerInterface;

class PackageUpdate extends PackageAbstractHandler implements HandlerInterface
{
    public function execute()
    {
        return $this->packageManager->update($this->packages);
    }

    public function getTitle()
    {
        return __('Update %1', implode(', ', $this->packages));
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
            SetupUpgrade::class => true,
            ProductionEnable::class => $this->isProduction(),
            MaintenanceDisable::class => true,
        ];
    }
}
