<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Api\HandlerInterface;

class PackageUpdate extends PackageAbstractHandler implements HandlerInterface
{
    public function execute()
    {
        return $this->packageManager->update($this->packageName);
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
            SetupUpgrade::class,
            MaintenanceDisable::class,
        ];
    }
}
