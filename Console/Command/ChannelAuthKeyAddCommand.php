<?php

namespace Swissup\Marketplace\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Swissup\Marketplace\Api\ChannelInterface;

class ChannelAuthKeyAddCommand extends ChannelAbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('marketplace:auth:key:add')
            ->setDescription('Add access key to the specified channel');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $channel = $this->getChannel();

            $this->enableChannel($channel);
            $this->addAccessKey($channel);

            $output->writeln('<info>The channel was saved</info>');

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
    protected function addAccessKey(ChannelInterface $channel)
    {
        if (!$channel->getAuthType()) {
            return;
        }

        $password = $channel->getPassword();
        $keys = explode(' ', $password);

        $key = $this->ask('Please enter your key: ');
        $channel->addData(['password' => $key]);
        $this->checkCredentials($channel);

        if (!in_array($key, $keys)) {
            $channel->addData(['password' => $password . ' ' . $key]);
        }

        $this->channelManager->saveCredentials($channel);
    }
}
