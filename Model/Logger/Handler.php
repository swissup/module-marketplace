<?php

namespace Swissup\Marketplace\Model\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Formatter\LineFormatter;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    // const LINE_FORMAT = "[%datetime%] %level_name%: %message%\n";
    const LINE_FORMAT = "%message%\n";

    const FILENAME = '/var/log/marketplace.log';

    /**
     * @param DriverInterface $filesystem
     * @param string $filePath
     * @param string $fileName
     * @throws \Exception
     */
    public function __construct(
        DriverInterface $filesystem,
        $filePath = null,
        $fileName = null
    ) {
        if (!$fileName) {
            $fileName = static::FILENAME;
        }

        parent::__construct($filesystem, $filePath, $fileName);

        $this->setFormatter(new LineFormatter(static::LINE_FORMAT, null, true));
    }
}
