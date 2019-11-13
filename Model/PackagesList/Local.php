<?php

namespace Swissup\Marketplace\Model\PackagesList;

use Magento\Framework\Component\ComponentRegistrar;

class Local extends AbstractList
{
    public function __construct(
        \Magento\Framework\Module\Dir\Reader $moduleDirReader,
        \Magento\Framework\Component\ComponentRegistrarInterface $registrar,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Magento\Framework\Filesystem\Driver\File $filesystemDriver
    ) {
        $this->moduleDirReader = $moduleDirReader;
        $this->registrar = $registrar;
        $this->jsonSerializer = $jsonSerializer;
        $this->filesystemDriver = $filesystemDriver;
    }

    /**
     * @return array
     */
    public function getList()
    {
        if ($this->isLoaded()) {
            return $this->data;
        }

        $components = [
            ComponentRegistrar::THEME,
            ComponentRegistrar::MODULE,
        ];

        $enabledModules = $this->moduleDirReader->getComposerJsonFiles()->toArray();

        foreach ($components as $component) {
            $paths = $this->registrar->getPaths($component);
            foreach ($paths as $name => $path) {
                $path = $path . '/composer.json';

                try {
                    $config = $this->filesystemDriver->fileGetContents($path);
                    $config = $this->jsonSerializer->unserialize($config);
                    $this->data[$config['name']] = $this->extractPackageData($config);
                    $this->data[$config['name']]['enabled'] =
                        $component === ComponentRegistrar::THEME || // themes are always enabled
                        !empty($enabledModules[$path]);
                } catch (\Exception $e) {
                    // skip module with broken composer.json file
                }
            }
        }

        $this->isLoaded(true);

        return $this->data;
    }
}
