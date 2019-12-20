<?php

namespace Swissup\Marketplace\Api;

interface HandlerInterface
{
    public function getTitle();

    public function execute();

    public function beforeQueue();

    public function afterQueue();
}
