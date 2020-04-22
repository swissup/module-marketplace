<?php

namespace Swissup\Marketplace\Api;

interface HandlerInterface
{
    public function getTitle();

    public function handle();

    public function setLogger(\Psr\Log\LoggerInterface $logger = null);

    public function getLogger();

    public function validateBeforeHandle();

    public function validateBeforeDispatch();

    public function beforeQueue();

    public function afterQueue();
}
