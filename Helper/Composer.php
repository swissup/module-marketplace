<?php

namespace Swissup\Marketplace\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;

class Composer
{
    public function __construct(
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Swissup\Marketplace\Model\ComposerApplication $composer,
        \Swissup\Marketplace\Model\Process $process
    ) {
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
        try {
            // touch auth.json
            $this->composer->runAuthCommand(['setting-key' => 'http-basic']);
        } catch (\Exception $e) {
            //
        }

        $result = [];

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
                try {
                    $existingData = $this->composer->runAuthCommand(['setting-key' => $authType]);
                    $existingData = $this->jsonSerializer->unserialize($existingData);
                } catch (\Exception $e) {
                    $existingData = [];
                }

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

                    $result[$path]['imported']++;

                    $this->composer->runAuthCommand([
                        'setting-key' => $authType . '.' . $host,
                        'setting-value' => $value,
                    ]);
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

        $root = BP . '/auth.json';
        if ($this->file->isReadable($root)) {
            $paths[$root] = $root;
        }

        if (!$paths) {
            throw new \Exception('Unable to locate and read auth.json file');
        }

        return $paths;
    }
}
