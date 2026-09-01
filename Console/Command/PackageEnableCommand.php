<?php

namespace Swissup\Marketplace\Console\Command;

use Swissup\Marketplace\Model\Handler\PackageEnable;

class PackageEnableCommand extends PackageAbstractCommand
{
    /**
     * @var string
     */
    protected $handlerClass = PackageEnable::class;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('marketplace:package:enable')
            ->setAliases(['marketplace:enable'])
            ->setDescription('Enable specified packages');

        parent::configure();
    }

    protected function getHandlerCmdOptions()
    {
        return PackageEnable::getAvailableCmdOptions();
    }
}
