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
        $command = array_merge([
            '-q' => true,
        ], $command);

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
}
