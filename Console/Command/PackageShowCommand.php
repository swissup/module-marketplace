<?php

namespace Swissup\Marketplace\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PackageShowCommand extends Command
{
    /**
     * @var \Swissup\Marketplace\Model\ComposerApplication
     */
    protected $composer;

    /**
     * @var \Swissup\Marketplace\Helper\Composer
     */
    protected $composerHelper;

    /**
     * @param \Swissup\Marketplace\Model\ComposerApplication $composer
     * @param \Swissup\Marketplace\Helper\Composer $composerHelper
     */
    public function __construct(
        \Swissup\Marketplace\Model\ComposerApplication $composer,
        \Swissup\Marketplace\Helper\Composer $composerHelper
    ) {
        $this->composer = $composer;
        $this->composerHelper = $composerHelper;
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
     * Initializes the command after the input has been bound and before the input
     * is validated.
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->composerHelper->importAuthCredentials();
        } catch (\Exception $e) {
            //
        }

        return parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->composer->run([
                'command' => 'show',
                'package' => $input->getArgument('package'),
                'version' => $input->getArgument('version'),
                '--no-interaction' => true,
                '--available' => true,
            ], $output);

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
