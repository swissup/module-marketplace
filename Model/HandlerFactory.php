<?php

namespace Swissup\Marketplace\Model;

use Swissup\Marketplace\Api\HandlerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class HandlerFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @var array
     */
    private $handlers;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param array $handlers
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        array $handlers
    ) {
        $this->objectManager = $objectManager;
        $this->jsonSerializer = $jsonSerializer;
        $this->handlers = $handlers;
    }

    /**
     * @param string|Job $class
     * @param array $arguments
     * @return HandlerInterface
     */
    public function create($class, array $arguments = [])
    {
        if ($class instanceof Job) {
            $arguments = $class->getArgumentsSerialized();
            $arguments = $this->jsonSerializer->unserialize($arguments);
            $class = $class->getClass();
        }

        if (!in_array($class, $this->handlers)) {
            throw new NoSuchEntityException(__('Handler "%1" is not registered.', $class));
        }

        if (!in_array(HandlerInterface::class, class_implements($class))) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Handler "%s" must implement \Swissup\Marketplace\Api\HandlerInterface.',
                    $class
                )
            );
        }

        return $this->objectManager->create($class, $arguments);
    }
}
