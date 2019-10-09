<?php

namespace Swissup\Marketplace\Ui\DataProvider;

use Magento\Ui\DataProvider\AddFieldToCollectionInterface;
use Swissup\Marketplace\Model\ResourceModel\Package\Collection;

class PackageDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * Construct
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Collection $collection
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Collection $collection,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collection;
    }
}
