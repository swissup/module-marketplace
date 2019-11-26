<?php

namespace Swissup\Marketplace\Model\PackagesList;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Composer\ComposerInformation;

class Local extends AbstractList
{
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var \Magento\Framework\Module\PackageInfo
     */
    protected $packageInfo;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonSerializer;

    /**
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\Module\PackageInfo $packageInfo
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Module\PackageInfo $packageInfo,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
    ) {
        $this->filesystem = $filesystem;
        $this->moduleList = $moduleList;
        $this->packageInfo = $packageInfo;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @return array
     */
    public function getList()
    {
        if ($this->isLoaded()) {
            return $this->data;
        }

        $this->isLoaded(true);

        $enabledModules = [];
        foreach ($this->moduleList->getNames() as $moduleName) {
            $packageName = $this->packageInfo->getPackageName($moduleName);
            if ($packageName) {
                $enabledModules[$packageName] = $packageName;
            }
        }

        $directory = $this->filesystem->getDirectoryRead(DirectoryList::ROOT);
        $data = $directory->readFile('composer.lock');
        $data = $this->jsonSerializer->unserialize($data);

        foreach ($data['packages'] as $config) {
            $this->data[$config['name']] = $this->extractPackageData($config);
            $this->data[$config['name']]['enabled'] =
                $config['type'] === ComposerInformation::THEME_PACKAGE_TYPE || // themes are always enabled
                !empty($enabledModules[$config['name']]);
        }

        return $this->data;
    }
}
