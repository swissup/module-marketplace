<?php

namespace Swissup\Marketplace\Job;

use Swissup\Marketplace\Api\JobInterface;

class PackageDisable extends PackageAbstractJob implements JobInterface
{
    public function execute()
    {
        return $this->packageManager->disable($this->packageName);
    }
}
