<?php

namespace Swissup\Marketplace\Console\Command;

use Symfony\Component\Console\Command\Command;
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
            InputArgument::OPTIONAL,
            'Path to auth.json file'
        );

        $this->addOption(
            'force',
            null,
            null,
            'Override existing auth parameters'
        );

        parent::configure();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        return parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // touch auth.json
        try {
            $this->composer->runAuthCommand(['setting-key' => 'http-basic']);
        } catch (\Exception $e) {
            //
        }

        try {
            $newData = $this->getAuthJsonData();
            $force = $input->getOption('force');
            $imported = 0;
            $skipped = 0;

            foreach ($newData as $authType => $values) {
                if (!$values) {
                    continue;
                }

                $existingData = $this->composer->runAuthCommand([
                    'setting-key' => $authType,
                ]);
                $existingData = $this->jsonSerializer->unserialize($existingData);

                foreach ($values as $host => $value) {
                    if (isset($existingData[$host]) && !$force) {
                        $skipped++;
                        continue;
                    }

                    if (!is_array($value)) {
                        $value = [$value];
                    } else {
                        $value = array_values($value);
                    }

                    $this->composer->runAuthCommand([
                        'setting-key' => $authType . '.' . $host,
                        'setting-value' => $value,
                    ]);
                    $imported++;
                }
            }

            $output->writeln(sprintf(
                '<info>Done. %s credential(s) imported. %s skipped.</info>',
                $imported,
                $skipped
            ));

            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->write($e->getTraceAsString());
            }

            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
    }

    /**
     * @return array
     */
    protected function getAuthJsonData()
    {
        $path = $this->input->getArgument('path');

        if (!$path) {
            $path = $this->findAuthJsonPath();
        }

        $json = $this->file->fileGetContents($path);

        return $this->jsonSerializer->unserialize($json);
    }

    /**
     * @return string
     */
    protected function findAuthJsonPath()
    {
        $path = false;
        $commands = [
            ['composer config home', null, false],
            ['composer.phar config home'],
        ];

        foreach ($commands as $command) {
            try {
                $home = $this->process->run(...$command);
                $home = trim($home);
                $path = $home . '/auth.json';
            } catch (\Exception $e) {
                //
            }
        }

        if (!$path && $this->file->isReadable(BP . '/auth.json')) {
            $path = BP . '/auth.json';
        }

        if (!$path) {
            throw new \Exception('Unable to locate auth.json file');
        }

        return $path;
    }
}
