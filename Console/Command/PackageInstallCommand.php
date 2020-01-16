<?php

namespace Swissup\Marketplace\Console\Command;

use Swissup\Marketplace\Model\Handler\PackageInstall;

class PackageInstallCommand extends PackageAbstractCommand
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
        $this->setName('marketplace:package:install')
            ->setDescription('Install specified packages');

        parent::configure();
    }
}
