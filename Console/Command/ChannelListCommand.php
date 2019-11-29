<?php

namespace Swissup\Marketplace\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChannelListCommand extends Command
{
    /**
     * @var \Swissup\Marketplace\Model\ChannelRepository
     */
    private $channelRepository;

    /**
     * @param \Swissup\Marketplace\Model\ChannelRepository $channelRepository
     */
    public function __construct(
        \Swissup\Marketplace\Model\ChannelRepository $channelRepository
    ) {
        $this->channelRepository = $channelRepository;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('marketplace:channel:list')
            ->setDescription('Displays a list of registered software channels');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $table = new Table($output);
            $table->setHeaders(['ID', 'Title', 'URL', 'Is Enabled']);

            foreach ($this->channelRepository->getList() as $channel) {
                $table->addRow([
                    $channel->getIdentifier(),
                    $channel->getTitle(),
                    $channel->getUrl(),
                    (int) $channel->isEnabled(),
                ]);
            }

            $table->render();

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
