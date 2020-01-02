<?php

namespace Swissup\Marketplace\Model\Handler;

use Magento\Framework\App\Filesystem\DirectoryList;
use Swissup\Marketplace\Api\HandlerInterface;

class CleanupFilesystem extends AbstractHandler implements HandlerInterface
{
    /**
     * @var \Magento\Framework\App\State\CleanupFiles
     */
    private $cleanupFiles;

    /**
     * @var \Magento\Deploy\Model\Filesystem
     */
    private $filesystem;

    /**
     * @param \Magento\Framework\App\State\CleanupFiles $cleanupFiles
     * @param \Magento\Deploy\Model\Filesystem $filesystem
     */
    public function __construct(
        \Magento\Framework\App\State\CleanupFiles $cleanupFiles,
        \Magento\Deploy\Model\Filesystem $filesystem
    ) {
        $this->cleanupFiles = $cleanupFiles;
        $this->filesystem = $filesystem;
    }

    public function getTitle()
    {
        return __('Cleanup Filesystem');
    }

    /**
     * @return void
     */
    public function execute()
    {
        $failed = false;
        $result = [$this->getTitle()];

        try {
            foreach ($this->cleanupFiles->clearCodeGeneratedFiles() as $path) {
                $result[] = $path;
            }
            $this->filesystem->cleanupFilesystem([DirectoryList::CACHE]);
        } catch (\Exception $e) {
            $failed = true;
            $result[] = $e->getMessage();
        }

        $result = implode("\n", array_filter($result));

        if ($failed) {
            throw new \Exception($result);
        }

        return $result;
    }
}
