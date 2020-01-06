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
            Additional\MaintenanceEnable::class => true,
            Additional\ProductionDisable::class => $this->isProduction(),
        ];
    }

    /**
     * @return array
     */
    public function afterQueue()
    {
        return [
            Additional\CleanupFilesystem::class => true,
            Additional\SetupUpgrade::class => true,
            Additional\ProductionEnable::class => $this->isProduction(),
            Additional\MaintenanceDisable::class => true,
        ];
    }
}
