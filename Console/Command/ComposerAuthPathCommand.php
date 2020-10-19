<?php

namespace Swissup\Marketplace\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ComposerAuthPathCommand extends Command
{
    /**
     * @var \Swissup\Marketplace\Model\ComposerApplication
     */
    private $composer;

    /**
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
        $this->setName('marketplace:auth:path')
            ->setDescription("Show path to auth.json file");

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln($this->composer->getAuthJsonPath());
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->write($e->getTraceAsString());
            }

            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }
}
