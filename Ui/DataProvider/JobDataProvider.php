<?php

namespace Swissup\Marketplace\Ui\DataProvider;

use Swissup\Marketplace\Model\HandlerFactory;
use Swissup\Marketplace\Model\ResourceModel\Job\Collection;

class JobDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var HandlerFactory
     */
    protected $handlerFactory;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Collection $collection
     * @param HandlerFactory $handlerFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Collection $collection,
        HandlerFactory $handlerFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collection;
        $this->handlerFactory = $handlerFactory;
    }

    /**
     * @return Collection
     */
    public function getData()
    {
        $collection = $this->getCollection();

        foreach ($collection as $item) {
            try {
                $title = $this->handlerFactory->create($item)->getTitle();
            } catch (\Exception $e) {
                $title = __('Unknown handler "%1"', $item->getClass());
            }
            $item->setTitle($title);
        }

        return $collection->toArray();
    }
}
