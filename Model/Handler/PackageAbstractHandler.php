<?php

namespace Swissup\Marketplace\Model\Handler;

class PackageAbstractHandler extends AbstractHandler
{
    /**
     * @var string
     */
    protected $packageName;

    /**
     * @var \Swissup\Marketplace\Model\PackageManager
     */
    protected $packageManager;

    /**
     * @param string $packageName
     * @param \Swissup\Marketplace\Model\PackageManager $packageManager
     */
    public function __construct(
        $packageName,
        \Swissup\Marketplace\Model\PackageManager $packageManager
    ) {
        $this->packageName = $packageName;
        $this->packageManager = $packageManager;
    }
}
