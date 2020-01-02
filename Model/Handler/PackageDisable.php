<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Api\HandlerInterface;

class PackageDisable extends PackageAbstractHandler implements HandlerInterface
{
    public function validate()
    {
        return $this->validateWhenDisable();
    }

    public function execute()
    {
        return $this->packageManager->disable($this->packages);
    }

    public function getTitle()
    {
        return __('Disable %1', implode(', ', $this->packages));
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
            CleanGeneratedFiles::class => true,
            ProductionEnable::class => $this->isProduction(),
            MaintenanceDisable::class => true,
        ];
    }
}
