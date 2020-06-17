<?php

namespace Swissup\Marketplace\Console\Command;

use Swissup\Marketplace\Model\Handler\PackageInstall;

class PackageRequireCommand extends PackageAbstractCommand
{
    /**
     * @var string
     */
    protected $handlerClass = PackageInstall::class;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('marketplace:package:require')
            ->setDescription('Download and enable specified packages');

        parent::configure();
    }

    protected function getHandlerCmdOptions()
    {
        return PackageInstall::getAvailableCmdOptions();
    }
}
