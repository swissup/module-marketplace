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
     * @var array
     */
    private $handlers;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $handlers
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $handlers
    ) {
        $this->objectManager = $objectManager;
        $this->handlers = $handlers;
    }

    /**
     * @param string $class
     * @param array $arguments
     * @return HandlerInterface
     */
    public function create($class, array $arguments = [])
    {
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
