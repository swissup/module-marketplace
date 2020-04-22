<?php

namespace Swissup\Marketplace\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PackageShowCommand extends Command
{
    public function __construct(
        \Swissup\Marketplace\Model\ComposerApplication $composer
    ) {
        $this->composer = $composer;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('marketplace:package:show')
            ->setDescription("Proxy to 'composer show --available' command");

        $this->addArgument(
            'package',
            InputArgument::REQUIRED,
            'Package Name'
        );

        $this->addArgument(
            'version',
            InputArgument::OPTIONAL,
            'Package Version'
        );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $result = $this->composer->run([
                'command' => 'show',
                'package' => $input->getArgument('package'),
                'version' => $input->getArgument('version'),
                '--no-interaction' => true,
                '--available' => true,
            ]);

            $output->write($result);

            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->write('<error>' . $e->getMessage() . '</error>');
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->write($e->getTraceAsString());
            }

            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
    }
}
