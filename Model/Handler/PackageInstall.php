<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Api\HandlerInterface;
use Swissup\Marketplace\Model\HandlerValidationException;

class PackageInstall extends PackageAbstractHandler implements HandlerInterface
{
    protected static $cmdOptions = [
        'profile',
        'ignore-platform-reqs',
    ];

    public function execute()
    {
        return $this->packageManager->install(
            $this->packages,
            $this->getCmdOptions(),
            $this->getOutput()
        );
    }

    public function validateBeforeHandle()
    {
        try {
            foreach ($this->packages as $package) {
                $package = explode(':', $package);
                $this->packageManager->show($package[0], ['--available' => true]);
            }
        } catch (\Exception $e) {
            preg_match('/(Package .* not found)/', $e->getMessage(), $matches);

            if (!empty($matches[1])) {
                throw new HandlerValidationException(__(
                    "Validation failed: %1",
                    $matches[1]
                ));
            }

            throw $e;
        }
    }

    public function getTitle()
    {
        return __('Install %1', implode(', ', $this->packages));
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
