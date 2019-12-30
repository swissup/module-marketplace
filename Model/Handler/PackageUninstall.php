<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Api\HandlerInterface;

class PackageUninstall extends PackageAbstractHandler implements HandlerInterface
{
    public function validate()
    {
        return $this->validateWhenDisable();
    }

    public function execute()
    {
        return $this->packageManager->uninstall($this->packages);
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
            MaintenanceEnable::class,
        ];
    }

    /**
     * @return array
     */
    public function afterQueue()
    {
        return [
            CleanGeneratedFiles::class,
            SetupUpgrade::class,
            MaintenanceDisable::class,
        ];
    }
}
