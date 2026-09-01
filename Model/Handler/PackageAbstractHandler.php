<?php

namespace Swissup\Marketplace\Model\Handler;

use Magento\Framework\App\State;
use Swissup\Marketplace\Model\HandlerValidationException;

class PackageAbstractHandler extends AbstractHandler
{
    /**
     * @var array
     */
    protected $packages;

    /**
     * State
     */
    protected $state;

    /**
     * @var \Swissup\Marketplace\Model\PackageManager
     */
    protected $packageManager;

    /**
     * @var \Magento\Framework\App\MaintenanceMode
     */
    protected $maintenanceMode;

    /**
     * @param array $packages
     * @param State $state
     * @param \Swissup\Marketplace\Model\PackageManager $packageManager
     * @param \Magento\Framework\App\MaintenanceMode $maintenanceMode
     * @param array $data
     */
    public function __construct(
        $packages,
        State $state,
        \Swissup\Marketplace\Model\PackageManager $packageManager,
        \Magento\Framework\App\MaintenanceMode $maintenanceMode,
        array $data = []
    ) {
        $this->packages = $packages;
        $this->state = $state;
        $this->packageManager = $packageManager;
        $this->maintenanceMode = $maintenanceMode;
        parent::__construct($data);
    }

    protected function isProduction()
    {
        return $this->state->getMode() === State::MODE_PRODUCTION;
    }

    protected function isMaintenanceEnabled()
    {
        return $this->maintenanceMode->isOn();
    }

    /**
     * @return boolean
     * @throws HandlerValidationException
     */
    protected function validateModules()
    {
        $invalid = $this->packageManager->getNonModulePackages($this->packages);

        if ($invalid) {
            throw new HandlerValidationException(__(
                'Only a module can be enabled or disabled. Not a module: %1',
                implode(', ', $invalid)
            ));
        }

        return true;
    }

    protected function validateWhenEnable()
    {
        return $this->processValidationResult(
            $this->packageManager->getConstraintsWhenEnable($this->packages)
        );
    }

    protected function validateWhenDisable()
    {
        return $this->processValidationResult(
            $this->packageManager->getConstraintsWhenDisable($this->packages)
        );
    }

    protected function processValidationResult(array $constraints)
    {
        if ($constraints['message']) {
            $exception = new HandlerValidationException($constraints['message']);
            $exception->setData($constraints);
            throw $exception;
        }

        return true;
    }
}
