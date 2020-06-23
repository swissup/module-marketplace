<?php

namespace Swissup\Marketplace\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ComposerAuthImportCommand extends Command
{
    /**
     * @var \Swissup\Marketplace\Helper\Composer
     */
    private $composerHelper;

    /**
     * @param \Swissup\Marketplace\Helper\Composer $composerHelper
     */
    public function __construct(
        \Swissup\Marketplace\Helper\Composer $composerHelper
    ) {
        $this->composerHelper = $composerHelper;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('marketplace:auth:import')
            ->setDescription("Import auth credentials from COMPOSER_HOME directory");

        $this->addArgument(
            'path',
            InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
            'Path to auth.json file'
        );

        $this->addOption(
            'force',
            'f',
            null,
            'Override existing auth parameters'
        );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $result = $this->composerHelper->importAuthCredentials(
                $input->getOption('force'),
                $input->getArgument('path')
            );
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->write($e->getTraceAsString());
            }

            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $table = new Table($output);
        $table->setHeaders(['Path', 'Imported', 'Skipped']);

        foreach ($result as $path => $values) {
            $table->addRow([
                $path,
                $values['imported'],
                $values['skipped'],
            ]);
        }

        $table->render();

        $output->writeln('<info>Done.</info>');

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }
}
