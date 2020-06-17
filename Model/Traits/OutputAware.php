<?php

namespace Swissup\Marketplace\Model\Traits;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

trait OutputAware
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output = null)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        if (!$this->output) {
            $this->output = new BufferedOutput();
        }

        return $this->output;
    }
}
