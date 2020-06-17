<?php

namespace Swissup\Marketplace\Console\Command;

use Swissup\Marketplace\Model\Handler\PackageUninstall;

class PackageRemoveCommand extends PackageAbstractCommand
{
    /**
     * @var string
     */
    protected $handlerClass = PackageUninstall::class;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('marketplace:package:remove')
            ->setDescription('Remove specified packages');

        parent::configure();
    }

    protected function getHandlerCmdOptions()
    {
        return PackageUninstall::getAvailableCmdOptions();
    }
}
