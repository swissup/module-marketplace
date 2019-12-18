<?php

namespace Swissup\Marketplace\Job;

class AbstractJob
{
    public function beforeQueue()
    {
        return [];
    }

    public function afterQueue()
    {
        return [];
    }
}
