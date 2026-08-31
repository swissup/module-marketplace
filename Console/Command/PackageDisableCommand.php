<?php

namespace Swissup\Marketplace\Console\Command;

use Swissup\Marketplace\Model\Handler\PackageDisable;

class PackageDisableCommand extends PackageAbstractCommand
{
    /**
     * @var string
     */
    protected $handlerClass = PackageDisable::class;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('marketplace:package:disable')
            ->setAliases(['marketplace:disable'])
            ->setDescription('Disable specified packages');

        parent::configure();
    }

    protected function getHandlerCmdOptions()
    {
        return PackageDisable::getAvailableCmdOptions();
    }
}
