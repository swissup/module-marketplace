<?php

namespace Swissup\Marketplace\Model;

use Magento\Framework\Config\File\ConfigFilePool;

class PackageManager
{
    /**
     * Package name to module name pairs
     *
     * @var array
     */
    protected $moduleNames = [];

    /**
     * Module name tp pacakge name pairs
     *
     * @var array
     */
    protected $packageNames = [];

    /**
     * @var \Magento\Framework\Module\PackageInfo
     */
    protected $packageInfo;

    /**
     * @var \Magento\Framework\Module\Status
     */
    protected $moduleStatus;

    /**
     * @var \Magento\Framework\App\DeploymentConfig\Reader
     */
    protected $configReader;

    /**
     * @var \Magento\Framework\App\DeploymentConfig\Writer
     */
    protected $configWriter;

    /**
     * @var \Swissup\Marketplace\Model\ComposerApplication
     */
    protected $composer;

    /**
     * @param \Magento\Framework\Module\PackageInfo $packageInfo
     * @param \Magento\Framework\Module\Status $moduleStatus
     * @param \Magento\Framework\App\DeploymentConfig\Reader $configReader
     * @param \Magento\Framework\App\DeploymentConfig\Writer $configWriter
     * @param \Swissup\Marketplace\Model\ComposerApplication $composer
     */
    public function __construct(
        \Magento\Framework\Module\PackageInfo $packageInfo,
        \Magento\Framework\Module\Status $moduleStatus,
        \Magento\Framework\Module\ConflictChecker $conflictChecker,
        \Magento\Framework\Module\DependencyChecker $dependencyChecker,
        \Magento\Framework\App\DeploymentConfig\Reader $configReader,
        \Magento\Framework\App\DeploymentConfig\Writer $configWriter,
        \Swissup\Marketplace\Model\ComposerApplication $composer
    ) {
        $this->packageInfo = $packageInfo;
        $this->moduleStatus = $moduleStatus;
        $this->conflictChecker = $conflictChecker;
        $this->dependencyChecker = $dependencyChecker;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->composer = $composer;
    }

    /**
     * @param array $packages
     * @return string
     * @throws \RuntimeException
     */
    public function install($packages)
    {
        return $this->composer->run([
            'command' => 'require',
            'packages' => $packages,
            '--no-progress' => true,
            '--no-interaction' => true,
            '--update-with-all-dependencies' => true,
            '--update-no-dev' => true,
        ]);
    }

    /**
     * @param array $packages
     * @return string
     * @throws \RuntimeException
     */
    public function uninstall($packages)
    {
        return $this->composer->run([
            'command' => 'remove',
            'packages' => $packages,
            '--no-progress' => true,
            '--no-interaction' => true,
            '--update-no-dev' => true,
        ]);
    }

    /**
     * @param array $packages
     * @return string
     * @throws \RuntimeException
     */
    public function update($packages)
    {
        return $this->composer->run([
            'command' => 'update',
            'packages' => $packages,
            '--no-progress' => true,
            '--no-interaction' => true,
            '--with-all-dependencies' => true,
            '--no-dev' => true,
        ]);
    }

    /**
     * @param array $packages
     * @return void
     */
    public function disable($packages)
    {
        $this->changeStatus($packages, false);
    }

    /**
     * @param array $packages
     * @return void
     */
    public function enable($packages)
    {
        $this->changeStatus($packages, true);
    }

    protected function changeStatus($packages, $flag)
    {
        if ($flag) {
            $constraints = $this->getConstraintsWhenEnable($packages);
        } else {
            $constraints = $this->getConstraintsWhenDisable($packages);
        }

        if ($constraints['message']) {
            throw new \Exception($constraints['message']);
        }

        $modules = [];
        foreach ($packages as $packageName) {
            $modules[] = $this->getModuleName($packageName);
        }

        $config = $this->configReader->load(ConfigFilePool::APP_CONFIG);
        foreach ($modules as $module) {
            $config['modules'][$module] = (int) $flag;
        }

        $this->configWriter->saveConfig(
            [ConfigFilePool::APP_CONFIG => $config],
            true
        );
    }

    protected function getModuleName($packageName)
    {
        if (isset($this->moduleNames[$packageName])) {
            return $this->moduleNames[$packageName];
        }

        $moduleName = $this->packageInfo->getModuleName($packageName);

        if (!$moduleName) {
            // if module is disabled
            list($vendor, $moduleName) = explode('/', $packageName);
            $moduleName = str_replace('module-', '', $moduleName);
            $moduleName = str_replace('-', '', ucwords($moduleName, '-'));
            $moduleName = ucfirst($vendor) . '_' . $moduleName;
        }

        $this->moduleNames[$packageName] = $moduleName;
        $this->packageNames[$moduleName] = $packageName;

        return $moduleName;
    }

    protected function getPackageName($moduleName)
    {
        if (isset($this->packageNames[$moduleName])) {
            return $this->packageNames[$moduleName];
        }

        $packageName = $this->packageInfo->getPackageName($moduleName);

        if (!$packageName) {
            // if module is disabled
            list($vendor, $packageName) = explode('_', $moduleName);
            $packageName = strtolower(trim(preg_replace('/([A-Z]|[0-9]+)/', "-$1", $packageName), '-'));
            $packageName = lcfirst($vendor) . '/module-' . $packageName;
        }

        $this->packageNames[$moduleName] = $packageName;

        return $this->packageNames[$moduleName];
    }

    public function getConstraintsWhenEnable($packages)
    {
        $modules = [];
        foreach ($packages as $packageName) {
            $modules[] = $this->getModuleName($packageName);
        }

        $message = '';
        $dependencies = $this->prepareConstraints(
            $this->dependencyChecker->checkDependenciesWhenEnableModules($modules)
        );
        $conflicts = $this->prepareConstraints(
            $this->conflictChecker->checkConflictsWhenEnableModules($modules)
        );

        if ($conflicts) {
            $message = __(
                "Cannot enable %1 because it conflicts with %2",
                implode(', ', $packages),
                implode(', ', $conflicts)
            );
        } elseif ($dependencies) {
            $message = __(
                "Cannot enable %1 because it requires the following dependencies: %2",
                implode(', ', $packages),
                implode(', ', $dependencies)
            );
        }

        return [
            'message' => $message,
            'dependencies' => $dependencies,
            'conflicts' => $conflicts,
        ];
    }

    public function getConstraintsWhenDisable($packages)
    {
        $modules = [];
        foreach ($packages as $packageName) {
            $modules[] = $this->getModuleName($packageName);
        }

        $message = '';
        $dependencies = $this->prepareConstraints(
            $this->dependencyChecker->checkDependenciesWhenDisableModules($modules)
        );

        if ($dependencies) {
            $message = __(
                "Cannot disable %1 because other modules uses it: %2",
                implode(', ', $packages),
                implode(', ', $dependencies)
            );
        }

        return [
            'message' => $message,
            'dependencies' => $dependencies,
        ];
    }

    protected function prepareConstraints(array $constraints)
    {
        $result = [];

        foreach ($constraints as $dependencies) {
            foreach ($dependencies as $moduleName => $chain) {
                $packageName = $this->getPackageName($moduleName);
                $result[$packageName] = $packageName;
            }
        }

        return array_values($result);
    }
}
