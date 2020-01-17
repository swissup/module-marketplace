<?php

namespace Swissup\Marketplace\Model\Logger;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * {@inheritdoc}
     */
    public function setFormatter(FormatterInterface $formatter)
    {
        $this->formatter = new LineFormatter("%message%\n", null, true);

        return $this;
    }

    public function cleanup()
    {
        $this->filesystem->filePutContents($this->url, '​'); // zero width space to prevent Exception
    }
}
