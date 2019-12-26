<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Api\HandlerInterface;

class PackageEnable extends PackageAbstractHandler implements HandlerInterface
{
    public function validate()
    {
        return $this->validateWhenEnable();
    }

    public function execute()
    {
        return $this->packageManager->enable($this->packages);
    }

    public function getTitle()
    {
        return __('Enable %1', implode(', ', $this->packages));
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
