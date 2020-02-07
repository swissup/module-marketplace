<?php

namespace Swissup\Marketplace\Console\Command;

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
     * @param \Symfony\Component\Console\Question\ChoiceQuestionFactory $choiceQuestionFactory
     * @param \Symfony\Component\Console\Helper\QuestionHelper $questionHelper
     * @param \Swissup\Marketplace\Model\PackagesList\LocalFactory $listFactory
     * @param \Swissup\Marketplace\Installer\Installer $installer
     */
    public function __construct(
        \Swissup\Marketplace\Model\HandlerFactory $handlerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Symfony\Component\Console\Question\ChoiceQuestionFactory $choiceQuestionFactory,
        \Symfony\Component\Console\Helper\QuestionHelper $questionHelper,
        \Swissup\Marketplace\Model\PackagesList\LocalFactory $listFactory,
        \Swissup\Marketplace\Installer\Installer $installer
    ) {
        $this->storeManager = $storeManager;
        $this->choiceQuestionFactory = $choiceQuestionFactory;
        $this->questionHelper = $questionHelper;
        $this->listFactory = $listFactory;
        $this->installer = $installer;

        parent::__construct($handlerFactory);
    }

    /**
     * Initializes the command after the input has been bound and before the input
     * is validated.
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);

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
        foreach ($fields as $name => $config) {
            $formData[$name] = $this->ask($config['title'], $config['options']);
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

    private function ask($title, $options, $multiple = false)
    {
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

            $answer = $this->questionHelper->ask(
                $this->input,
                $this->output,
                $question
            );
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

    private function getStoreList()
    {
        $result = [
            0 => (string) __('All Store Views'),
        ];

        foreach ($this->storeManager->getStores() as $id => $store) {
            $result[$id] = sprintf(
                '%s [%s]',
                str_pad($store->getName(), 20),
                $store->getCode()
            );
        }

        return $result;
    }
}
