<?php

namespace Swissup\Marketplace\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChannelDisableCommand extends Command
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
     * @param \Swissup\Marketplace\Model\ChannelRepository $channelRepository
     * @param \Swissup\Marketplace\Model\ChannelManager $channelManager
     */
    public function __construct(
        \Swissup\Marketplace\Model\ChannelRepository $channelRepository,
        \Swissup\Marketplace\Model\ChannelManager $channelManager
    ) {
        $this->channelRepository = $channelRepository;
        $this->channelManager = $channelManager;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('marketplace:channel:disable')
            ->setDescription('Disables specified channels');

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
        try {
            $disabled = [];
            $channels = $input->getArgument(self::INPUT_KEY_CHANNELS);

            foreach ($this->channelRepository->getList(true) as $channel) {
                if (!in_array($channel->getIdentifier(), $channels)) {
                    continue;
                }

                $this->channelManager->disable($channel);
                $disabled[] = $channel->getIdentifier();
            }

            if (!$disabled) {
                $message = 'No channels were changed.';
            } elseif (count($disabled) === 1) {
                $message = 'The channel was disabled';
            } else {
                $message = sprintf('%d channels were disabled', count($disabled));
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
}
