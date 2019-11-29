<?php

namespace Swissup\Marketplace\Job;

use Swissup\Marketplace\Api\JobInterface;

class PackageInstall extends PackageAbstractJob implements JobInterface
{
    public function execute()
    {
        $this->packageManager->install($this->packageName);
    }
}
