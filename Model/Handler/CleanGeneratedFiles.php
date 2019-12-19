<?php

namespace Swissup\Marketplace\Model\Handler;

use Magento\Framework\App\Filesystem\DirectoryList;
use Swissup\Marketplace\Api\HandlerInterface;

class CleanGeneratedFiles extends AbstractHandler implements HandlerInterface
{
    /**
     * @var \Magento\Framework\App\Cache\Manager
     */
    private $cacheManager;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList;

    /**
     * @var WriteInterface
     */
    private $write;

    /**
     * @param \Magento\Framework\App\Cache\Manager $cacheManager
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Directory\WriteFactory $writeFactory
     */
    public function __construct(
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Directory\WriteFactory $writeFactory
    ) {
        $this->cacheManager = $cacheManager;
        $this->directoryList = $directoryList;
        $this->write = $writeFactory->create(BP);
    }

    /**
     * Don't use GeneratedFiles::requestRegeneration 'cos is has a
     * race condition bug that leads to disabled cache.
     *
     * @return void
     */
    public function execute()
    {
        $cacheTypes = [];
        foreach ($this->cacheManager->getStatus() as $type => $status) {
            if (!$status) {
                continue;
            }
            $cacheTypes[] = $type;
        }

        $paths = [
            $this->write->getRelativePath($this->directoryList->getPath(DirectoryList::CACHE)),
            $this->write->getRelativePath($this->directoryList->getPath(DirectoryList::GENERATED_CODE)),
            $this->write->getRelativePath($this->directoryList->getPath(DirectoryList::GENERATED_METADATA)),
        ];

        foreach ($paths as $path) {
            if ($this->write->isDirectory($path)) {
                try {
                    $this->write->delete($path);
                } catch (\Exception $e) {
                    //
                }
            }
        }

        if ($cacheTypes) {
            $this->cacheManager->setEnabled($cacheTypes, true);
        }
    }
}
