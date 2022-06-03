<?php

namespace Swissup\Marketplace\Installer\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Swissup\Marketplace\Installer\Request;
use Swissup\Marketplace\Model\Traits\LoggerAware;

class Unpack
{
    use LoggerAware;

    /**
     * @var \Magento\Framework\Archive
     */
    private $archiver;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    private $ioFile;

    /**
     * @param \Magento\Framework\Archive $archiver
     * @param \Magento\Framework\Filesystem\Io\File $ioFile
     */
    public function __construct(
        \Magento\Framework\Archive $archiver,
        \Magento\Framework\Filesystem\Io\File $ioFile
    ) {
        $this->archiver = $archiver;
        $this->ioFile = $ioFile;
    }

    /**
     * @param Request $request
     * @return void
     */
    public function execute(Request $request)
    {
        $this->logger->info('Unpack');
        $params = $request->getParams();
        $destanation = $params['destination'];
        $this->ioFile->checkAndCreateFolder($destanation);
        $archive = $params['archive'];
        $this->archiver->unpack($archive, $destanation);
    }
}
