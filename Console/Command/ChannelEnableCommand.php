<?php

namespace Swissup\Marketplace\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\QuestionFactory;
use Swissup\Marketplace\Api\ChannelInterface;

class ChannelEnableCommand extends Command
{
    const INPUT_KEY_CHANNELS = 'channel';

    /**
     * @var \Swissup\Marketplace\Model\ChannelRepository
     */
    private $channelRepository;

    /**
     * @var \Swissup\Marketplace\Model\ChannelManager
     */
    private $channelManager;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param \Swissup\Marketplace\Model\ChannelRepository $channelRepository
     * @param \Swissup\Marketplace\Model\ChannelManager $channelManager
     */
    public function __construct(
        \Swissup\Marketplace\Model\ChannelRepository $channelRepository,
        \Swissup\Marketplace\Model\ChannelManager $channelManager,
        QuestionFactory $questionFactory,
        QuestionHelper $questionHelper
    ) {
        $this->channelRepository = $channelRepository;
        $this->channelManager = $channelManager;
        $this->questionFactory = $questionFactory;
        $this->questionHelper = $questionHelper;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('marketplace:channel:enable')
            ->setDescription('Enabled specified channels');

        $this->addArgument(
            self::INPUT_KEY_CHANNELS,
            InputArgument::IS_ARRAY | InputArgument::REQUIRED,
            'ID of the Channel'
        );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        try {
            $enabled = [];
            $channels = $input->getArgument(self::INPUT_KEY_CHANNELS);

            foreach ($this->channelRepository->getList() as $channel) {
                if (!in_array($channel->getIdentifier(), $channels)) {
                    continue;
                }

                $this->channelManager->enable($channel);
                $enabled[] = $channel->getIdentifier();

                $this->saveCredentials($channel);
            }

            if (!$enabled) {
                $message = 'No channels were changed.';
            } elseif (count($enabled) === 1) {
                $message = 'The channel was enabled';
            } else {
                $message = sprintf('%d channels were enabled', count($enabled));
            }

            $output->writeln('<info>' . $message . '</info>');

            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln($e->getTraceAsString());
            }

            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
    }

    /**
     * @param ChannelInterface $channel
     * @return void
     */
    protected function saveCredentials(ChannelInterface $channel)
    {
        if (!$channel->getAuthType()) {
            return;
        }

        $username = $channel->getUsername();
        $password = $channel->getPassword();

        if ($username && $password) {
            return;
        }

        if (!$username) {
            $channel->addData([
                'username' => $this->ask('Please enter your username: '),
            ]);
        }

        if (!$password) {
            $channel->addData([
                'password' => $this->ask('Please enter your password/private_key/access_key: '),
            ]);
        }

        $packages = $channel->getPackages();

        $this->output->writeln('<info>Credentials has been accepted</info>');
        $this->output->writeln('<info>Channel returned ' . count($packages) . ' package(s)</info>');

        $this->channelManager->saveCredentials($channel);
    }

    /**
     * @param string $question
     * @return string
     */
    protected function ask($question)
    {
        $question = $this->questionFactory->create(['question' => $question]);
        $question->setValidator(function ($value) {
            if (trim($value) == '') {
                throw new \Exception('Value cannot be empty');
            }

            return $value;
        });

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }
}
