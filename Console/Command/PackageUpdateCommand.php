<?php

namespace Swissup\Marketplace\Console\Command;

use Swissup\Marketplace\Model\Handler\PackageUpdate;

class PackageUpdateCommand extends PackageAbstractCommand
{
    /**
     * @var string
     */
    protected $handlerClass = PackageUpdate::class;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('marketplace:package:update')
            ->setDescription('Update specified packages');

        parent::configure();
    }

    protected function getHandlerCmdOptions()
    {
        return PackageUpdate::getAvailableCmdOptions();
    }
}
