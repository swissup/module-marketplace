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
                $this->data[$packageName] = [
                    'name' => $packageName,
                    'enabled' => true,
                    'version' => $this->packageInfo->getVersion($moduleName),
                ];
            }
        }

        $directory = $this->filesystem->getDirectoryRead(DirectoryList::ROOT);

        $json = $directory->readFile('composer.json');
        $json = $this->jsonSerializer->unserialize($json);

        $data = $directory->readFile('composer.lock');
        $data = $this->jsonSerializer->unserialize($data);

        foreach ($data['packages'] as $config) {
            $data = $this->extractPackageData($config);
            if (!empty($this->data[$config['name']]['version'])) {
                $data['version'] = $this->data[$config['name']]['version'];
            }

            $this->data[$config['name']] = $data;
            $this->data[$config['name']]['enabled'] = true;
            $this->data[$config['name']]['composer'] = isset($json['require'][$config['name']]);

            if ($config['type'] === ComposerInformation::MODULE_PACKAGE_TYPE) {
                $this->data[$config['name']]['enabled'] = !empty($enabledModules[$config['name']]);
            }
        }

        return $this->data;
    }
}
