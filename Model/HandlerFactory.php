<?php

namespace Swissup\Marketplace\Model;

class HandlerFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array
     */
    private $handlers;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $handlers
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $handlers = []
    ) {
        $this->objectManager = $objectManager;
        $this->handlers = $handlers;
    }

    public function create($class, array $arguments = [])
    {
        if (!in_array($class, $this->handlers)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid handler type "%s"', $class)
            );
        }

        $handler = $this->objectManager->create($class, $arguments);

        if (!$handler instanceof \Swissup\Marketplace\Api\HandlerInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Handler class "%s" must implement \Swissup\Marketplace\Api\HandlerInterface.',
                    get_class($handler)
                )
            );
        }

        return $handler;
    }
}
