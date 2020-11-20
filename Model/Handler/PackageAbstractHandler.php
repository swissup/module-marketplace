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
     * \Magento\Framework\Module\ConflictChecker
     */
    protected $conflictChecker;

    /**
     * \Magento\Framework\Module\DependencyChecker
     */
    protected $dependencyChecker;

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
     * @param \Magento\Framework\Module\ConflictChecker $conflictChecker
     * @param \Magento\Framework\Module\DependencyChecker $dependencyChecker
     * @param \Swissup\Marketplace\Model\PackageManager $packageManager
     * @param \Magento\Framework\App\MaintenanceMode $maintenanceMode
     * @param array $data
     */
    public function __construct(
        $packages,
        State $state,
        \Magento\Framework\Module\ConflictChecker $conflictChecker,
        \Magento\Framework\Module\DependencyChecker $dependencyChecker,
        \Swissup\Marketplace\Model\PackageManager $packageManager,
        \Magento\Framework\App\MaintenanceMode $maintenanceMode,
        array $data = []
    ) {
        $this->packages = $packages;
        $this->state = $state;
        $this->conflictChecker = $conflictChecker;
        $this->dependencyChecker = $dependencyChecker;
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
