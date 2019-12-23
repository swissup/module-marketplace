<?php

namespace Swissup\Marketplace\Model\Handler;

class PackageAbstractHandler extends AbstractHandler
{
    /**
     * @var array
     */
    protected $packages;

    /**
     * @var \Swissup\Marketplace\Model\PackageManager
     */
    protected $packageManager;

    /**
     * @param array $packages
     * @param \Swissup\Marketplace\Model\PackageManager $packageManager
     */
    public function __construct(
        $packages,
        \Swissup\Marketplace\Model\PackageManager $packageManager
    ) {
        $this->packages = $packages;
        $this->packageManager = $packageManager;
    }
}
