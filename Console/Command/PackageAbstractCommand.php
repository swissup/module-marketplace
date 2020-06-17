<?php

namespace Swissup\Marketplace\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class PackageAbstractCommand extends Command
{
    const INPUT_KEY_PACKAGE = 'package';

    /**
     * @var \Swissup\Marketplace\Model\HandlerFactory
     */
    protected $handlerFactory;

    /**
     * @var string
     */
    protected $handlerClass;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Swissup\Marketplace\Model\HandlerFactory $handlerFactory
     */
    public function __construct(
        \Swissup\Marketplace\Model\HandlerFactory $handlerFactory
    ) {
        $this->handlerFactory = $handlerFactory;
        parent::__construct();
    }

    /**
     * Initializes the command after the input has been bound and before the input
     * is validated.
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->logger = new ConsoleLogger($this->output);

        return parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addArgument(
            self::INPUT_KEY_PACKAGE,
            InputArgument::IS_ARRAY | InputArgument::REQUIRED,
            'Package Name'
        );

        parent::configure();
    }

    protected function getPackages()
    {
        return $this->input->getArgument(self::INPUT_KEY_PACKAGE);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $success = false;
        $handler = $this->createHandler($this->getHandlerClass(), [
            'packages' => $this->getPackages(),
        ]);

        try {
            $output->writeln('<info>Validating</info>');
            $handler->validateBeforeHandle();
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                $output->writeln($e->getTraceAsString());
            }
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $before = array_keys(array_filter($handler->beforeQueue()));
        $after = array_keys(array_filter($handler->afterQueue()));

        $this->processTasks($before);

        try {
            $output->writeln('<info>' . $handler->getTitle() . '</info>');
            $handler->handle();
            $success = true;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                $output->writeln($e->getTraceAsString());
            }
        }

        $this->processTasks($after);

        return $success ?
            \Magento\Framework\Console\Cli::RETURN_SUCCESS :
            \Magento\Framework\Console\Cli::RETURN_FAILURE;
    }

    /**
     * @param array $tasks
     * @return void
     */
    protected function processTasks(array $tasks)
    {
        foreach ($tasks as $task) {
            try {
                $handler = $this->createHandler($task);
                $this->output->writeln('<info>' . $handler->getTitle() . '</info>');
                $handler->validateBeforeHandle();
                $handler->handle();
            } catch (\Exception $e) {
                $this->output->writeln('<error>' . $e->getMessage() . '</error>');
                if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                    $this->output->writeln($e->getTraceAsString());
                }
            }
        }
    }

    /**
     * @return string
     */
    protected function getHandlerClass()
    {
        if (!$this->handlerClass) {
            throw new \Exception('Handler class name is not defined');
        }
        return $this->handlerClass;
    }

    /**
     * @param string $class
     * @param array $arguments
     * @return \Swissup\Marketplace\Model\Handler\AbstractHandler
     */
    protected function createHandler($class, array $arguments = [])
    {
        return $this->handlerFactory
            ->create($class, $arguments)
            ->setOutput($this->output)
            ->setLogger($this->logger);
    }
}
