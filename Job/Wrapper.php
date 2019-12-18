<?php

namespace Swissup\Marketplace\Job;

use Swissup\Marketplace\Api\JobInterface;

class Wrapper extends AbstractJob implements JobInterface
{
    /**
     * @var array
     */
    private $jobs = [];

    /**
     * @param \Swissup\Marketplace\Service\JobDispatcher $dispatcher
     */
    public function __construct(
        \Swissup\Marketplace\Service\JobDispatcher $dispatcher
    ) {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param array $jobs
     */
    public function addJobs(array $jobs = [])
    {
        foreach ($jobs as $job) {
            $this->jobs[$job] = $job;
        }
    }

    public function execute()
    {
        $output = [];

        foreach ($this->jobs as $job) {
            $output[] = $this->dispatcher->dispatchNow($job);
        }

        return implode("\n\n", array_filter($output));
    }
}
