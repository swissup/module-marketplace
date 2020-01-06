<?php

namespace Swissup\Marketplace\Model\Handler\Additional;

use Magento\Framework\App\Filesystem\DirectoryList;
use Swissup\Marketplace\Api\HandlerInterface;
use Swissup\Marketplace\Model\Handler\AbstractHandler;

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
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\State\CleanupFiles $cleanupFiles,
        \Magento\Deploy\Model\Filesystem $filesystem,
        array $data = []
    ) {
        $this->cleanupFiles = $cleanupFiles;
        $this->filesystem = $filesystem;
        parent::__construct($data);
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
