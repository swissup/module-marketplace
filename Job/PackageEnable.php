<?php

namespace Swissup\Marketplace\Job;

use Swissup\Marketplace\Api\JobInterface;

class PackageEnable extends PackageAbstractJob implements JobInterface
{
    public function execute()
    {
        return $this->packageManager->enable($this->packageName);
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
            MaintenanceDisable::class,
        ];
    }
}
