<?php

namespace Swissup\Marketplace\Model\Handler;

class AbstractHandler
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
