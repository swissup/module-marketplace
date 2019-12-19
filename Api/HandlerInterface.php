<?php

namespace Swissup\Marketplace\Api;

interface HandlerInterface
{
    public function execute();

    public function beforeQueue();

    public function afterQueue();
}
