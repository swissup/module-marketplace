<?php

namespace Swissup\Marketplace\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ComposerAuthCommand extends Command
{
    /**
     * @var \Swissup\Marketplace\Model\ComposerApplication
     */
    private $composer;

    /**
     *
     * @param \Swissup\Marketplace\Model\ComposerApplication $composer
     */
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
        $this->setName('marketplace:auth')
            ->setDescription("Proxy to 'composer config --auth' command");

        $this->addArgument(
            'setting-key',
            InputArgument::REQUIRED,
            'Setting Key'
        );

        $this->addArgument(
            'setting-value',
            InputArgument::IS_ARRAY,
            'Setting Value'
        );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $result = $this->composer->runAuthCommand([
                'setting-key' => $input->getArgument('setting-key'),
                'setting-value' => $input->getArgument('setting-value'),
            ]);

            if (!$result) {
                $output->writeln('<info>Done!</info>');
            } else {
                $output->write('<info>' . $result . '</info>');
            }

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
