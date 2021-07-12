<?php

namespace Swissup\Marketplace\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;

class Composer
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    private $file;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @var \Swissup\Marketplace\Model\ComposerApplication
     */
    private $composer;

    /**
     * @var \Swissup\Marketplace\Model\Process
     */
    private $process;

    /**
     * Composer constructor.
     * @param DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Swissup\Marketplace\Model\ComposerApplication $composer
     * @param \Swissup\Marketplace\Model\Process $process
     */
    public function __construct(
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Swissup\Marketplace\Model\ComposerApplication $composer,
        \Swissup\Marketplace\Model\Process $process
    ) {
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->jsonSerializer = $jsonSerializer;
        $this->composer = $composer;
        $this->process = $process;
    }

    /**
     * @param boolean $force
     * @param array $paths
     * @return array
     * @throws \Exception
     */
    public function importAuthCredentials($force = false, array $paths = [])
    {
        $result = [];

        $authJsonData = $this->getAuthJsonData();

        if (!$paths) {
            $paths = $this->findAuthJsonPaths();
        }

        foreach ($paths as $path) {
            $result[$path] = [
                'imported' => 0,
                'skipped' => 0,
            ];

            try {
                $newData = $this->file->fileGetContents($path);
                $newData = $this->jsonSerializer->unserialize($newData);
            } catch (\Exception $e) {
                continue;
            }

            foreach ($newData as $authType => $values) {
                $existingData = $authJsonData[$authType] ?? [];

                foreach ($values as $host => $value) {
                    if (isset($existingData[$host]) && !$force) {
                        $result[$path]['skipped']++;
                        continue;
                    }

                    if (!is_array($value)) {
                        $value = [$value];
                    } else {
                        $value = array_values($value);
                    }

                    try {
                        $this->composer->runAuthCommand([
                            'setting-key' => $authType . '.' . $host,
                            'setting-value' => $value,
                        ]);
                        $result[$path]['imported']++;
                    } catch (\Exception $e) {
                        $result[$path]['skipped']++;
                        continue;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function findAuthJsonPaths()
    {
        $paths = [];
        $commands = [
            ['composer config home', null, false],
            ['composer.phar config home'],
        ];

        foreach ($commands as $command) {
            try {
                $path = $this->process->run(...$command);
            } catch (\Exception $e) {
                continue;
            }

            $path = trim($path);
            $path = $path . '/auth.json';

            if ($this->file->isReadable($path)) {
                $paths[$path] = $path;
                break;
            }
        }

        if ($this->composer->canUseRootAuthJson()) {
            // imposer from old composer_home/auth.json file
            $root = $this->directoryList->getPath(DirectoryList::COMPOSER_HOME) . '/auth.json';
        } else {
            // import from root/auth.json file
            $root = $this->directoryList->getPath(DirectoryList::ROOT) . '/auth.json';
        }

        if ($this->file->isReadable($root)) {
            $paths[$root] = $root;
        }

        if (!$paths) {
            throw new \Exception('Unable to locate and read auth.json file');
        }

        return $paths;
    }

    /**
     * @return array
     */
    protected function getAuthJsonData()
    {
        $path = $this->composer->getAuthJsonPath();

        if (!$this->file->isReadable($path)) {
            return [];
        }

        try {
            $data = $this->file->fileGetContents($path);
            $data = $this->jsonSerializer->unserialize($data);
        } catch (\Exception $e) {
            $data = [];
        }

        return $data;
    }
}
