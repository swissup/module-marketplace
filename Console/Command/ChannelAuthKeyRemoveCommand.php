<?php

namespace Swissup\Marketplace\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Swissup\Marketplace\Api\ChannelInterface;

class ChannelAuthKeyRemoveCommand extends ChannelAbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('marketplace:auth:key:remove')
            ->setDescription('Remove specified key from the specified channel');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $channel = $this->getChannel();

            $this->removeAccessKey($channel);

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
    protected function removeAccessKey(ChannelInterface $channel)
    {
        if (!$channel->getAuthType()) {
            return;
        }

        $password = $channel->getPassword();
        $keys = explode(' ', $password);

        $key = $this->ask(
            'Please enter the key to remove: ',
            function ($value) use ($keys) {
                if (trim($value) == '') {
                    throw new \Exception('Value cannot be empty');
                }

                if (!in_array($value, $keys)) {
                    throw new \Exception('Can\'t find the the key to remove');
                }

                return $value;
            }
        );

        $channel->addData([
            'password' => str_replace($key, '', $password),
        ]);

        $this->channelManager->saveCredentials($channel);
    }
}
