<?php

namespace Swissup\Marketplace\Api;

interface JobInterface
{
    public function execute();

    public function beforeQueue();

    public function afterQueue();
}
