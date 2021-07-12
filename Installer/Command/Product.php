<?php

namespace Swissup\Marketplace\Installer\Command;

use Swissup\Marketplace\Installer\Request;

class Product extends ProductCollection
{
    /**
     * @param Request $request
     * @return void
     */
    public function execute(Request $request)
    {
        // $this->logger->warning('Product Command is deprecated. Please use ProductCollection instead');

        parent::execute($request);
    }
}
