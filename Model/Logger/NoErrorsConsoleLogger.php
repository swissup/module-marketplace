<?php

namespace Swissup\Marketplace\Model\Logger;

use Symfony\Component\Console\Logger\ConsoleLogger;

class NoErrorsConsoleLogger extends ConsoleLogger
{
    /**
     * This class is used to suppress errors created in
     * \Magento\ComposerRootUpdatePlugin\Utils\Console::log method.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function error($message, array $context = array())
    {
        $this->info($message, $context);
    }
}
