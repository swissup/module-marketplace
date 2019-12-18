<?php

namespace Swissup\Marketplace\Job;

use Swissup\Marketplace\Api\JobInterface;

class SetupUpgrade extends AbstractJob implements JobInterface
{
    /**
     * \Magento\Framework\Console\Cli
     */
    private $cli;

    /**
     * \Symfony\Component\Console\Input\ArrayInputFactory
     */
    private $inputFactory;

    /**
     * \Symfony\Component\Console\Output\BufferedOutputFactory
     */
    private $outputFactory;

    /**
     * @param \Magento\Framework\Console\Cli $cli
     * @param \Symfony\Component\Console\Input\ArrayInputFactory $inputFactory
     * @param \Symfony\Component\Console\Output\BufferedOutputFactory $outputFactory
     */
    public function __construct(
        \Magento\Framework\Console\Cli $cli,
        \Symfony\Component\Console\Input\ArrayInputFactory $inputFactory,
        \Symfony\Component\Console\Output\BufferedOutputFactory $outputFactory
    ) {
        $this->cli = $cli;
        $this->inputFactory = $inputFactory;
        $this->outputFactory = $outputFactory;
    }

    /**
     * @return string
     */
    public function execute()
    {
        $input = $this->inputFactory->create([
            'parameters' => ['command' => 'setup:upgrade']
        ]);

        $output = $this->outputFactory->create();

        $this->cli->find('setup:upgrade')->run($input, $output);

        return $output->fetch();
    }
}
