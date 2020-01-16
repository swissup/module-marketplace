<?php

namespace Swissup\Marketplace\Console\Command;

use Swissup\Marketplace\Model\Handler\PackageUninstall;

class PackageUninstallCommand extends PackageAbstractCommand
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
        $this->setName('marketplace:package:uninstall')
            ->setDescription('Uninstall specified packages');

        parent::configure();
    }
}
