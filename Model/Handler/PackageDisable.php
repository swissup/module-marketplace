<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Api\HandlerInterface;

class PackageDisable extends PackageAbstractHandler implements HandlerInterface
{
    public function execute()
    {
        return $this->packageManager->disable($this->packageName);
    }

    public function getTitle()
    {
        return __('Disable %1', $this->packageName);
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