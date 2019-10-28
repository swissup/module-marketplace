<?php

namespace Swissup\Marketplace\Model;

class PackageManager
{
    public function __construct(
        \Magento\Framework\Module\PackageInfo $packageInfo,
        \Magento\Framework\Module\Status $moduleStatus,
        \Magento\Framework\Code\GeneratedFiles $generatedFiles,
        \Swissup\Marketplace\Model\ResourceModel\Package\CollectionFactory $packageCollectionFactory
    ) {
        $this->packageInfo = $packageInfo;
        $this->moduleStatus = $moduleStatus;
        $this->generatedFiles = $generatedFiles;
        $this->packageCollection = $packageCollectionFactory->create();
    }

    public function get($packageName)
    {
        return $this->packageCollection->getItemById($packageName);
    }

    public function disable($packageName)
    {
        $this->changeStatus($packageName, false);
    }

    public function enable($packageName)
    {
        $this->changeStatus($packageName, true);
    }

    public function changeStatus($packageName, $flag)
    {
        $this->moduleStatus->setIsEnabled($flag, [$this->getModuleName($packageName)]);
        $this->generatedFiles->requestRegeneration();
    }

    public function getModuleName($packageName)
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
