<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Api\HandlerInterface;

class PackageUninstall extends PackageAbstractHandler implements HandlerInterface
{
    protected static $cmdOptions = [
        'profile',
        'ignore-platform-reqs',
    ];

    public function execute()
    {
        return $this->packageManager->uninstall(
            $this->packages,
            $this->getCmdOptions(),
            $this->getOutput()
        );
    }

    public function validateBeforeDispatch()
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
            Additional\MaintenanceEnable::class => !$this->isMaintenanceEnabled(),
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
            Additional\MaintenanceDisable::class => !$this->isMaintenanceEnabled(),
        ];
    }
}
