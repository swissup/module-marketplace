<?php

namespace Swissup\Marketplace\Job;

use Swissup\Marketplace\Api\JobInterface;

class PackageEnable extends PackageAbstractJob implements JobInterface
{
    public function execute()
    {
        return $this->packageManager->enable($this->packageName);
    }
}
