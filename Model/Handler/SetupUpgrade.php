<?php

namespace Swissup\Marketplace\Model\Handler;

use Swissup\Marketplace\Api\HandlerInterface;

class SetupUpgrade extends AbstractHandler implements HandlerInterface
{
    /**
     * \Magento\Framework\Console\CliFactory
     */
    private $cliFactory;

    /**
     * \Symfony\Component\Console\Input\ArrayInputFactory
     */
    private $inputFactory;

    /**
     * \Symfony\Component\Console\Output\BufferedOutputFactory
     */
    private $outputFactory;

    /**
     * @param \Magento\Framework\Console\CliFactory $cliFactory
     * @param \Symfony\Component\Console\Input\ArrayInputFactory $inputFactory
     * @param \Symfony\Component\Console\Output\BufferedOutputFactory $outputFactory
     */
    public function __construct(
        \Magento\Framework\Console\CliFactory $cliFactory,
        \Symfony\Component\Console\Input\ArrayInputFactory $inputFactory,
        \Symfony\Component\Console\Output\BufferedOutputFactory $outputFactory
    ) {
        $this->cliFactory = $cliFactory;
        $this->inputFactory = $inputFactory;
        $this->outputFactory = $outputFactory;
    }

    public function getTitle()
    {
        return __('bin/magento setup:upgrade');
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

        $this->cliFactory->create()->find('setup:upgrade')->run($input, $output);

        return $output->fetch();
    }
}
