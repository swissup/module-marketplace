<?php

namespace Swissup\Marketplace\Model;

class ComposerApplication
{
    /**
     * @var \Magento\Composer\MagentoComposerApplication
     */
    protected $app;

    /**
     * @var \Magento\Framework\Composer\MagentoComposerApplicationFactory
     */
    protected $appFactory;

    /**
     * @param \Magento\Framework\Composer\MagentoComposerApplicationFactory $appFactory
     */
    public function __construct(
        \Magento\Framework\Composer\MagentoComposerApplicationFactory $appFactory
    ) {
        $this->appFactory = $appFactory;
    }

    /**
     * @param array $command
     * @return string
     */
    public function run(array $command)
    {
        $this->updateMemoryLimit();

        return $this->getApp()->runComposerCommand($command);
    }

    /**
     * @return \Magento\Framework\Composer\MagentoComposerApplication
     */
    protected function getApp()
    {
        if (!$this->app) {
            $this->app = $this->appFactory->create();
        }
        return $this->app;
    }

    /**
     * @return void
     */
    private function updateMemoryLimit()
    {
        if (function_exists('ini_set')) {
            $memoryLimit = trim(ini_get('memory_limit'));
            if ($memoryLimit != -1 && $this->getMemoryInBytes($memoryLimit) < 2000 * 1024 * 1024) {
                ini_set('memory_limit', '2G');
            }
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
}
