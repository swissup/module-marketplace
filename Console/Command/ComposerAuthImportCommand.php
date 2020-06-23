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
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    private $file;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @var \Swissup\Marketplace\Model\ComposerApplication
     */
    private $composer;

    /**
     * @var \Swissup\Marketplace\Model\Process
     */
    private $process;

    /**
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Swissup\Marketplace\Model\ComposerApplication $composer
     * @param \Swissup\Marketplace\Model\Process $process
     */
    public function __construct(
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Swissup\Marketplace\Model\ComposerApplication $composer,
        \Swissup\Marketplace\Model\Process $process
    ) {
        $this->file = $file;
        $this->jsonSerializer = $jsonSerializer;
        $this->composer = $composer;
        $this->process = $process;
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

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        try {
            // touch auth.json
            $this->composer->runAuthCommand(['setting-key' => 'http-basic']);
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
        $paths = $input->getArgument('path');
        $force = $input->getOption('force');
        $result = [];

        if (!$paths) {
            $paths = $this->findAuthJsonPaths();
        }

        foreach ($paths as $path) {
            $result[$path] = [
                'imported' => 0,
                'skipped' => 0,
            ];

            try {
                $newData = $this->file->fileGetContents($path);
                $newData = $this->jsonSerializer->unserialize($newData);
            } catch (\Exception $e) {
                continue;
            }

            foreach ($newData as $authType => $values) {
                try {
                    $existingData = $this->composer->runAuthCommand(['setting-key' => $authType]);
                    $existingData = $this->jsonSerializer->unserialize($existingData);
                } catch (\Exception $e) {
                    $existingData = [];
                }

                foreach ($values as $host => $value) {
                    if (isset($existingData[$host]) && !$force) {
                        $result[$path]['skipped']++;
                        continue;
                    }

                    if (!is_array($value)) {
                        $value = [$value];
                    } else {
                        $value = array_values($value);
                    }

                    $result[$path]['imported']++;

                    try {
                        $this->composer->runAuthCommand([
                            'setting-key' => $authType . '.' . $host,
                            'setting-value' => $value,
                        ]);
                    } catch (\Exception $e) {
                        $output->writeln('<error>' . $e->getMessage() . '</error>');
                        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                            $output->write($e->getTraceAsString());
                        }

                        return \Magento\Framework\Console\Cli::RETURN_FAILURE;
                    }
                }
            }
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

    /**
     * @return array
     */
    protected function findAuthJsonPaths()
    {
        $paths = [];
        $commands = [
            ['composer config home', null, false],
            ['composer.phar config home'],
        ];

        foreach ($commands as $command) {
            try {
                $path = $this->process->run(...$command);
            } catch (\Exception $e) {
                continue;
            }

            $path = trim($path);
            $path = $path . '/auth.json';

            if ($this->file->isReadable($path)) {
                $paths[$path] = $path;
                break;
            }
        }

        $root = BP . '/auth.json';
        if ($this->file->isReadable($root)) {
            $paths[$root] = $root;
        }

        if (!$paths) {
            throw new \Exception('Unable to locate and read auth.json file');
        }

        return $paths;
    }
}
