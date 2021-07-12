<?php

namespace Swissup\Marketplace\Service;

use Magento\Framework\Stdlib\DateTime;
use Swissup\Marketplace\Model\Job;

class JobDispatcher
{
    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    private $remoteAddress;

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
     * @var \Swissup\Marketplace\Model\Logger\Handler
     */
    private $logHandler;

    /**
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Swissup\Marketplace\Model\HandlerFactory $handlerFactory
     * @param \Swissup\Marketplace\Model\JobFactory $jobFactory
     * @param \Swissup\Marketplace\Model\Logger\Handler $logHandler
     */
    public function __construct(
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Swissup\Marketplace\Helper\Data $helper,
        \Swissup\Marketplace\Model\HandlerFactory $handlerFactory,
        \Swissup\Marketplace\Model\JobFactory $jobFactory,
        \Swissup\Marketplace\Model\Logger\Handler $logHandler
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->jsonSerializer = $jsonSerializer;
        $this->helper = $helper;
        $this->handlerFactory = $handlerFactory;
        $this->jobFactory = $jobFactory;
        $this->logHandler = $logHandler;
    }

    /**
     * @param string $class
     * @param array $arguments
     * @return Job
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function dispatch($class, array $arguments = [])
    {
        if (!isset($arguments['data']['ip'])) {
            $arguments['data']['ip'] = $this->remoteAddress->getRemoteAddress();
        }

        $this->logHandler->cleanup();

        $this->handlerFactory->create($class, $arguments)->validateBeforeDispatch();

        $job = $this->jobFactory->create(['data' => [
            'class' => $class,
            'arguments_serialized' => $this->jsonSerializer->serialize($arguments),
            'created_at' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
            'status' => Job::STATUS_PENDING,
        ]]);

        return $job
            ->setSignature($this->helper->generateJobSignature($job))
            ->save();
    }
}
