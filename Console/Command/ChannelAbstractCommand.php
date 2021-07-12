<?php

namespace Swissup\Marketplace\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\QuestionFactory;
use Symfony\Component\Console\Question\ChoiceQuestionFactory;
use Swissup\Marketplace\Api\ChannelInterface;
use Swissup\Marketplace\Helper\Composer;
use Swissup\Marketplace\Model\ChannelManager;
use Swissup\Marketplace\Model\ChannelRepository;

class ChannelAbstractCommand extends Command
{
    const INPUT_KEY_CHANNEL = 'channel';

    /**
     * @var ChannelRepository
     */
    protected $channelRepository;

    /**
     * @var ChannelManager
     */
    protected $channelManager;

    /**
     * @var Composer
     */
    protected $composerHelper;

    /**
     * @var QuestionFactory
     */
    protected $questionFactory;

    /**
     * @var ChoiceQuestionFactory
     */
    protected $choiceQuestionFactory;

    /**
     * @var QuestionHelper
     */
    protected $questionHelper;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param ChannelRepository $channelRepository
     * @param ChannelManager $channelManager
     * @param QuestionFactory $questionFactory
     * @param ChoiceQuestionFactory $choiceQuestionFactory
     * @param QuestionHelper $questionHelper
     */
    public function __construct(
        ChannelRepository $channelRepository,
        ChannelManager $channelManager,
        Composer $composerHelper,
        QuestionFactory $questionFactory,
        ChoiceQuestionFactory $choiceQuestionFactory,
        QuestionHelper $questionHelper
    ) {
        $this->channelRepository = $channelRepository;
        $this->channelManager = $channelManager;
        $this->composerHelper = $composerHelper;
        $this->questionFactory = $questionFactory;
        $this->choiceQuestionFactory = $choiceQuestionFactory;
        $this->questionHelper = $questionHelper;
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

        try {
            $this->composerHelper->importAuthCredentials();
        } catch (\Exception $e) {
            //
        }

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
        $this->addArgument(
            self::INPUT_KEY_CHANNEL,
            InputArgument::OPTIONAL,
            'ID of the Channel'
        );

        parent::configure();
    }

    /**
     * @return ChannelInterface
     */
    protected function getChannel()
    {
        $channel = $this->input->getArgument(self::INPUT_KEY_CHANNEL);

        if (!$channel) {
            $question = $this->choiceQuestionFactory->create([
                'question' => 'Please, select a channel: ',
                'choices' => array_map(function ($item) {
                    return $item->getIdentifier();
                }, $this->channelRepository->getList()),
            ]);

            $channel = $this->questionHelper->ask($this->input, $this->output, $question);
        }

        return $this->channelRepository->getById($channel);
    }

    /**
     * @param ChannelInterface $channel
     * @return $this
     */
    protected function enableChannel($channel)
    {
        $this->channelManager->enable($channel);
        return $this;
    }

    /**
     * @param ChannelInterface $channel
     * @return $this
     */
    protected function disableChannel($channel)
    {
        $this->channelManager->disable($channel);
        return $this;
    }

    /**
     * @param ChannelInterface $channel
     * @return $this
     */
    protected function checkCredentials($channel)
    {
        $packages = $channel->getPackages();

        $this->output->writeln('<info>Credentials has been accepted</info>');
        $this->output->writeln('<info>Channel returned ' . count($packages) . ' package(s)</info>');

        return $this;
    }

    /**
     * @param ChannelInterface $channel
     * @param boolean $force
     * @return void
     */
    protected function askAndSaveCredentials(ChannelInterface $channel, $force = false)
    {
        if (!$channel->getAuthType()) {
            return;
        }

        $username = $channel->getUsername();
        $password = $channel->getPassword();

        if ($force === false && $username && $password) {
            return;
        }

        $notice = $channel->getCliAuthNotice();
        foreach (explode('\n', $notice) as $line) {
            $this->output->writeln($line);
        }

        if ($force || !$username) {
            $channel->addData([
                'username' => $this->ask('Please enter your username: '),
            ]);
        }

        if ($force || !$password) {
            $channel->addData([
                'password' => $this->ask('Please enter your password/private_key/access_key: '),
            ]);
        }

        $this->checkCredentials($channel);

        $this->channelManager->saveCredentials($channel);
    }

    /**
     * @param ChannelInterface $channel
     * @return void
     */
    protected function askAndAddAccessKey(ChannelInterface $channel)
    {
        if (!$channel->getAuthType()) {
            return;
        }

        $password = $channel->getPassword();
        $keys = explode(' ', $password);

        $notice = $channel->getCliAuthNotice();
        foreach (explode('\n', $notice) as $line) {
            $this->output->writeln($line);
        }

        $key = $this->ask(sprintf('Please enter key for <fg=green;options=bold>%s</>: ', $channel->getUsername()));
        $channel->addData(['password' => $key]);
        $this->checkCredentials($channel);

        $keys[] = $key;
        $keys = array_unique($keys);
        $channel->addData(['password' => implode(' ', $keys)]);

        $this->channelManager->saveCredentials($channel);
    }

    /**
     * @param string $question
     * @return string
     */
    protected function ask($question, callable $validator = null)
    {
        $question = $this->questionFactory->create(['question' => $question]);

        if ($validator === null) {
            $question->setValidator(function ($value) {
                if (trim($value) == '') {
                    throw new \Exception('Value cannot be empty');
                }

                return $value;
            });
        } elseif ($validator) {
            $question->setValidator($validator);
        }

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }
}
