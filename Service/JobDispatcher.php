<?php

namespace Swissup\Marketplace\Service;

use Magento\Framework\Stdlib\DateTime;
use Swissup\Marketplace\Model\Job;

class JobDispatcher
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @var \Swissup\Marketplace\Helper\Data
     */
    private $helper;

    /**
     * @var \Swissup\Marketplace\Model\HandlerFactory
     */
    private $handlerFactory;

    /**
     * @var \Swissup\Marketplace\Model\JobFactory
     */
    private $jobFactory;

    /**
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Swissup\Marketplace\Helper\Data $helper
     * @param \Swissup\Marketplace\Model\HandlerFactory $handlerFactory
     * @param \Swissup\Marketplace\Model\JobFactory $jobFactory
     */
    public function __construct(
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Swissup\Marketplace\Helper\Data $helper,
        \Swissup\Marketplace\Model\HandlerFactory $handlerFactory,
        \Swissup\Marketplace\Model\JobFactory $jobFactory
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->helper = $helper;
        $this->handlerFactory = $handlerFactory;
        $this->jobFactory = $jobFactory;
    }

    /**
     * @param string $class
     * @param array $arguments
     * @return mixed
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function dispatch($class, array $arguments = [])
    {
        if ($this->helper->canUseAsyncMode()) {
            $this->createHandler($class, $arguments)->validate();
            return $this->dispatchToQueue($class, $arguments);
        } else {
            return $this->dispatchNow($class, $arguments);
        }
    }

    /**
     * @param string $class
     * @param array $arguments
     * @return mixed
     */
    public function dispatchNow($class, array $arguments = [])
    {
        $handler = $this->createHandler($class, $arguments);

        $handler->validate();

        return $handler->execute();
    }

    /**
     * @param string $class
     * @param array $arguments
     * @return Job
     */
    public function dispatchToQueue($class, array $arguments = [])
    {
        return $this->jobFactory->create()
            ->addData([
                'class' => $class,
                'arguments_serialized' => $this->jsonSerializer->serialize($arguments),
                'created_at' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
                'status' => Job::STATUS_PENDING,
            ])
            ->save();
    }

    protected function createHandler($class, array $arguments = [])
    {
        return $this->handlerFactory->create($class, $arguments);
    }
}
