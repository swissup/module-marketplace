<?php

namespace Swissup\Marketplace\Model;

use Magento\Framework\Config\File\ConfigFilePool;

class PackageManager
{
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
        \Magento\Framework\App\DeploymentConfig\Reader $configReader,
        \Magento\Framework\App\DeploymentConfig\Writer $configWriter,
        \Swissup\Marketplace\Model\ComposerApplication $composer
    ) {
        $this->packageInfo = $packageInfo;
        $this->moduleStatus = $moduleStatus;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->composer = $composer;
    }

    public function install($packageName)
    {
        return $this->composer->run([
            'command' => 'require',
            'packages' => [$packageName],
            '--no-progress' => true,
            '--no-interaction' => true,
            '--update-with-all-dependencies' => true,
            '--update-no-dev' => true,
        ]);
    }

    public function uninstall($packageName)
    {
        $this->disable($packageName);

        return $this->composer->run([
            'command' => 'remove',
            'packages' => [$packageName],
            '--no-progress' => true,
            '--no-interaction' => true,
            '--update-no-dev' => true,
        ]);
    }

    public function update($packageName)
    {
        return $this->composer->run([
            'command' => 'update',
            'packages' => [$packageName],
            '--no-progress' => true,
            '--no-interaction' => true,
            '--with-all-dependencies' => true,
            '--no-dev' => true,
        ]);
    }

    public function disable($packageName)
    {
        $this->changeStatus($packageName, false);
    }

    public function enable($packageName)
    {
        $this->changeStatus($packageName, true);
    }

    protected function changeStatus($packageName, $flag)
    {
        $module = $this->getModuleName($packageName);

        $constraints = $this->moduleStatus->checkConstraints($flag, [$module]);
        if ($constraints) {
            throw new \Exception(sprintf(
                "Unable to change status of module because of the following constraints: \n%s",
                implode("\n", $constraints)
            ));
        }

        $config = $this->configReader->load(ConfigFilePool::APP_CONFIG);
        $config['modules'][$module] = (int) $flag;

        $this->configWriter->saveConfig(
            [ConfigFilePool::APP_CONFIG => $config],
            true
        );
    }

    protected function getModuleName($packageName)
    {
        $moduleName = $this->packageInfo->getModuleName($packageName);

        if (!$moduleName) {
            // if module is disabled
            list($vendor, $moduleName) = explode('/', $packageName);
            $moduleName = str_replace('module-', '', $moduleName);
            $moduleName = str_replace('-', '', ucwords($moduleName, '-'));
            $moduleName = ucfirst($vendor) . '_' . $moduleName;
        }

        return $moduleName;
    }
}
