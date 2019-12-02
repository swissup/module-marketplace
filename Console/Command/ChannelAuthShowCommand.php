<?php

namespace Swissup\Marketplace\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChannelAuthShowCommand extends ChannelAbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('marketplace:auth:show')
            ->setDescription('Show auth credentials for the specified channel');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $channel = $this->getChannel();

            $output->write('Username: ');
            $output->writeln('<info>' . $channel->getUsername() . '</info>');
            $output->write('Password: ');
            $output->writeln('<info>' . $channel->getPassword() . '</info>');

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
