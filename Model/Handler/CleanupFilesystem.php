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
        return __('Filesystem Cleanup');
    }

    /**
     * @return void
     */
    public function execute()
    {
        try {
            $this->cleanupFiles->clearCodeGeneratedFiles();
            $this->filesystem->cleanupFilesystem([DirectoryList::CACHE]);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
