<?php

namespace Swissup\Marketplace\Console\Command;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Swissup\Marketplace\Model\Handler\PackageInstall;

class PackageInstallCommand extends PackageAbstractCommand
{
    const INPUT_KEY_STORE = 'store';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Symfony\Component\Console\Question\QuestionFactory
     */
    protected $questionFactory;

    /**
     * @var \Symfony\Component\Console\Question\ChoiceQuestionFactory
     */
    protected $choiceQuestionFactory;

    /**
     * @var \Symfony\Component\Console\Helper\QuestionHelper
     */
    protected $questionHelper;

    /**
     * @var \Swissup\Marketplace\Model\PackagesList\LocalFactory
     */
    protected $listFactory;

    /**
     * @var \Swissup\Marketplace\Installer\Installer
     */
    protected $installer;

    /**
     * @param \Swissup\Marketplace\Model\HandlerFactory $handlerFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Symfony\Component\Console\Question\QuestionFactory $questionFactory
     * @param \Symfony\Component\Console\Question\ChoiceQuestionFactory $choiceQuestionFactory
     * @param \Symfony\Component\Console\Helper\QuestionHelper $questionHelper
     * @param \Swissup\Marketplace\Model\PackagesList\LocalFactory $listFactory
     * @param \Swissup\Marketplace\Installer\Installer $installer
     */
    public function __construct(
        \Swissup\Marketplace\Model\HandlerFactory $handlerFactory,
        \Swissup\Marketplace\Helper\Composer $composerHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Symfony\Component\Console\Question\QuestionFactory $questionFactory,
        \Symfony\Component\Console\Question\ChoiceQuestionFactory $choiceQuestionFactory,
        \Symfony\Component\Console\Helper\QuestionHelper $questionHelper,
        \Swissup\Marketplace\Model\PackagesList\LocalFactory $listFactory,
        \Swissup\Marketplace\Installer\Installer $installer
    ) {
        $this->storeManager = $storeManager;
        $this->questionFactory = $questionFactory;
        $this->choiceQuestionFactory = $choiceQuestionFactory;
        $this->questionHelper = $questionHelper;
        $this->listFactory = $listFactory;
        $this->installer = $installer;

        parent::__construct($handlerFactory, $composerHelper);
    }

    /**
     * Initializes the command after the input has been bound and before the input
     * is validated.
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);

        if (!function_exists('exec')) {
            if (method_exists(QuestionHelper::class, 'disableStty')) {
                QuestionHelper::disableStty();
            }
        }

        return parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('marketplace:package:install')
            ->setDescription('Run installer for specified packages');

        $this->addOption(
            self::INPUT_KEY_STORE,
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
            'Store ID'
        );

        parent::configure();
    }

    protected function getPackages()
    {
        $packages = [];

        foreach (parent::getPackages() as $argument) {
            if (strpos($argument, '=') !== false) {
                continue;
            }
            $packages[] = $argument;
        }

        if (!$packages) {
            throw new \RuntimeException('Package name is missing');
        }

        return $packages;
    }

    protected function getRequestParams()
    {
        $params = [];

        foreach (parent::getPackages() as $argument) {
            if (strpos($argument, '=') === false) {
                continue;
            }

            list($key, $value) = explode('=', $argument);

            if (isset($params[$key])) {
                if (!is_array($params[$key])) {
                    $params[$key] = [$params[$key]];
                }
                $params[$key][] = $value;
            } else {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packages = $this->getPackages();
        $installed = array_keys($this->listFactory->create()->getList());
        $missing = array_diff($packages, $installed);

        if ($missing) {
            $output->writeln('<error>Installation canceled. Some packages not found.</error>');
            $output->writeln('Try downloading them using the following command:');
            $output->writeln(sprintf('bin/magento marketplace:package:require %s', implode(' ', $missing)));
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        if (!$this->installer->hasInstaller($packages)) {
            $output->writeln('<info>Nothing to do.</info>');
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $storeIds = $this->getStoreIds();
        $formData = [];

        $fields = $this->installer->getFormConfig($packages);
        $params = $this->getRequestParams();
        foreach ($fields as $name => $config) {
            if (isset($params[$name])) {
                $formData[$name] = $params[$name];
            } else {
                $formData[$name] = $this->ask($config['title'], $config['options'] ?? null);
            }
        }

        $this->installer
            ->setLogger($this->logger)
            ->run($packages, array_merge($formData, [
                'store_id' => $storeIds,
                'packages' => $packages,
            ]));

        $output->writeln('<info>Done.</info>');

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }

    private function getStoreIds()
    {
        $input = $this->input->getOption(self::INPUT_KEY_STORE);

        if (!$input) {
            $result = $this->askStoreIds();
        } else {
            // fix for the case when user entered --store=1,2 instead of --store=1 --store=2
            $result = [];
            foreach ($input as $ids) {
                foreach (explode(',', $ids) as $id) {
                    $result[] = $id;
                }
            }
        }

        return $result;
    }

    private function askStoreIds()
    {
        $stores = $this->getStoreList();

        $codes = $this->ask(
            (string) __('Please, select a Store'),
            $stores,
            true
        );

        $ids = [];
        $codeToId = array_flip($stores);
        foreach ($codes as $code) {
            $ids[] = $codeToId[$code];
        }

        return $ids;
    }

    private function ask($title, $options = null, $multiple = false)
    {
        if (!is_array($options)) {
            $question = $this->questionFactory->create([
                'question' => $title . ': ',
            ]);

            return $this->questionHelper->ask($this->input, $this->output, $question);
        }

        $choices = is_array(current($options)) ?
            $this->optionsToChoices($options) : $options;

        if (count($choices) > 1) {
            $question = $this->choiceQuestionFactory->create([
                'question' => $title,
                'choices' => $choices,
            ]);

            if ($multiple) {
                $question->setMultiselect(true);
            }

            $answer = $this->questionHelper->ask($this->input, $this->output, $question);
        } else {
            $answer = key($choices);
        }

        return $answer;
    }

    private function optionsToChoices($options)
    {
        $choices = [];
        foreach ($options as $option) {
            $choices[$option['value']] = $option['label'];
        }
        return $choices;
    }

    /**
     * Get the list of the stores WITHOUT WHITESPACES!
     * Symfony multiselect losing whitespaces.
     *
     * symfony/console/Question/ChoiceQuestion.php:133 and 140:
     * $selectedChoices = str_replace(' ', '', $selected);
     * $selectedChoices = explode(',', $selectedChoices);
     */
    private function getStoreList()
    {
        $result = [
            '0' => (string) __('All'),
        ];

        foreach ($this->storeManager->getStores() as $id => $store) {
            $result[(string)$id] = sprintf(
                '%s.[%s]',
                str_replace(' ', ' ', str_pad($store->getName(), 20, '.')),
                $store->getCode()
            );
        }

        return $result;
    }
}
