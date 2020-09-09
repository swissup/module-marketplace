<?php

namespace Swissup\Marketplace\Service;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\ValidatorException;

class Validator
{
    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    private $fileDriver;

    /**
     * @param \Magento\Framework\Filesystem $filesystem
     * @param DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $fileDriver
    ) {
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
    }

    /**
     * @return void
     * @throws ValidatorException
     */
    public function validate()
    {
        $this->validateMemoryLimit();
        $this->validatePermissions();
    }

    private function validateMemoryLimit()
    {
        if (!function_exists('ini_set')) {
            return;
        }

        $memoryRequired = 2200 * 1024 * 1024;

        $memoryLimit = trim(ini_get('memory_limit'));
        if ($memoryLimit != -1 && $this->getMemoryInBytes($memoryLimit) < $memoryRequired) {
            ini_set('memory_limit', -1);
        }

        $memoryLimit = trim(ini_get('memory_limit'));
        if ($memoryLimit != -1 && $this->getMemoryInBytes($memoryLimit) < $memoryRequired) {
            throw new ValidatorException(
                __(
                    "Marketplace requires 2G of memory. %1M is available.",
                    $this->getMemoryInBytes($memoryLimit) / 1024 / 1024
                )
            );
        }
    }

    /**
     * @param string $value
     * @return int
     */
    private function getMemoryInBytes($value)
    {
        $unit = strtolower(substr($value, -1, 1));
        $value = (int) $value;
        switch ($unit) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
        }
        return $value;
    }

    private function validatePermissions()
    {
        $directory = $this->filesystem->getDirectoryRead(DirectoryList::ROOT);
        $writableIfExist = [
            $this->directoryList->getPath(DirectoryList::COMPOSER_HOME),
            $this->directoryList->getPath(DirectoryList::COMPOSER_HOME) . '/auth.json',
        ];

        foreach ($writableIfExist as $path) {
            $path = $directory->getAbsolutePath($path);

            if ($this->fileDriver->isExists($path) && !$this->fileDriver->isWritable($path)) {
                throw new ValidatorException(__("The '%1' is not writable.", $path));
            }
        }

        $writablePaths = [
            $this->directoryList->getPath(DirectoryList::CONFIG) . '/config.php',
            $this->directoryList->getPath(DirectoryList::VAR_DIR),
            $this->directoryList->getPath(DirectoryList::LOG),
            'composer.json',
            'composer.lock',
            'vendor',
            'vendor/composer',
        ];

        foreach ($writablePaths as $path) {
            $path = $directory->getAbsolutePath($path);

            if (!$this->fileDriver->isWritable($path)) {
                throw new ValidatorException(__("The '%1' is not writable.", $path));
            }
        }
    }
}
