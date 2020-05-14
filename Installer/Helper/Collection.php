<?php

namespace Swissup\Marketplace\Installer\Helper;

class Collection
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $class
     * @param array $filters
     * @return \Magento\Framework\Data\Collection
     */
    protected function prepareCollection($class, array $filters = [])
    {
        $collection = $this->objectManager->create($class);

        foreach ($filters as $filter) {
            if (isset($filter['field'], $filter['value'])) {
                $collection->addFieldToFilter($filter['field'], $filter['value']);
            } elseif (isset($filter['method'], $filter['params'])) {
                call_user_func_array([$collection, $filter['method']], $filter['params']);
            }
        }

        return $collection;
    }

    public function getCollection(array $request, $class, array $filters = [])
    {
        return $this->prepareCollection($class, $filters);
    }

    /**
     * @param array $request
     * @param string $class
     * @param array $filters
     * @return int|string
     */
    public function getId(array $request, $class, array $filters = [])
    {
        return $this->prepareCollection($class, $filters)->setPageSize(1)->getFirstItem()->getId();
    }

    /**
     * @param array $request
     * @param string $class
     * @param array $filters
     * @return array
     */
    public function getIds(array $request, $class, array $filters = [])
    {
        return $this->prepareCollection($class, $filters)->getAllIds();
    }
}
