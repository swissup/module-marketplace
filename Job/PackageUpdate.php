<?php

namespace Swissup\Marketplace\Job;

use Swissup\Marketplace\Api\JobInterface;

class PackageUpdate extends PackageAbstractJob implements JobInterface
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
