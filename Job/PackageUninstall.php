<?php

namespace Swissup\Marketplace\Job;

use Swissup\Marketplace\Api\JobInterface;

class PackageUninstall extends PackageAbstractJob implements JobInterface
{
    public function execute()
    {
        $this->packageManager->uninstall($this->packageName);
    }
}
