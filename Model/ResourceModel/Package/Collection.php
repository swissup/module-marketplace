<?php

namespace Swissup\Marketplace\Model\ResourceModel\Package;

use Magento\Framework\Data\Collection\EntityFactoryInterface;

class Collection extends \Magento\Framework\Data\Collection
{
    /**
     * @var string
     */
    protected $_itemObjectClass = \Swissup\Marketplace\Model\Package::class;

    /**
     * @var array
     */
    protected $data = [];

    public function __construct(
        EntityFactoryInterface $entityFactory,
        \Swissup\Marketplace\Model\PackagesList\Local $localPackages,
        \Swissup\Marketplace\Model\PackagesList\Remote $remotePackages
    ) {
        $this->localPackages = $localPackages;
        $this->remotePackages = $remotePackages;

        return parent::__construct($entityFactory);
    }

    /**
     * Load data
     *
     * @return $this
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }

        $this->_setIsLoaded(true);

        $localPackages = $this->localPackages->getList();
        $remotePackages = $this->remotePackages->getList();

        foreach ($remotePackages as $id => $remoteData) {
            if (!empty($remoteData['marketplace']['hidden']) ||
                !empty($localPackages[$id]['marketplace']['hidden'])
            ) {
                continue;
            }

            $localVersion = $localPackages[$id]['version'] ?? false;

            $this->data[$id] = [
                'name' => $id,
                'type' => $remoteData['type'] ?? '',
                'description' => $remoteData['description'] ?? '',
                'image_src' => $remoteData['marketplace']['gallery'][0] ??
                    ($localPackages[$id]['marketplace']['gallery'][0] ?? false),
                'keywords' => $remoteData['keywords'] ?? [],
                'version' => $localVersion,
                'time' => $remoteData['versions'][$localVersion]['time'] ?? false,
                'installed' => isset($localPackages[$id]),
                'enabled' => $localPackages[$id]['enabled'] ?? false,
                'remote' => $remoteData,
                'local' => $localPackages[$id] ?? false,
            ];

            if (!$this->data[$id]['version']) {
                $code = 'na';
            } else {
                $updated = version_compare(
                    $this->data[$id]['version'],
                    $remoteData['version'],
                    '>='
                );
                $code = $updated ? 'updated' : 'outdated';
            }

            $this->data[$id]['state'] = $code;
        }

        $this->_totalRecords = count($this->data);

        $this->_renderFilters();
        $this->_renderOrders();
        $this->_renderLimit();

        foreach ($this->data as $values) {
            $item = $this->getNewEmptyItem();
            $item->setData($values);
            $item->setId($values['name']);

            $this->addItem($item);
        }


        return $this;
    }

    /**
     * @return $this
     */
    protected function _renderFilters()
    {
        if ($this->_isFiltersRendered) {
            return $this;
        }

        $this->data = array_filter($this->data, function ($item) {
            foreach ($this->_filters as $filter) {
                $value = $filter->getValue();
                $keywords = implode(' ', $item['keywords']);
                $require = '';

                if (!empty($item['remote']['require'])) {
                    $require = implode(' ', array_keys($item['remote']['require']));
                }

                if ($filter->getField() === 'fulltext') {
                    if (strpos($item['name'], $value) === false &&
                        strpos($item['description'], $value) === false &&
                        strpos($keywords, $value) === false &&
                        strpos($require, $value) === false
                    ) {
                        return false;
                    }
                }
            }

            return true;
        });

        $this->_isFiltersRendered = true;

        return $this;
    }

    /**
     * Sort items as follows:
     *
     *  - outdated
     *  - updated
     *  - disabled
     *  - na
     *
     *  @return $this
     */
    protected function _renderOrders()
    {
        usort($this->data, function ($a, $b) {
            if ($a['installed'] === $b['installed']) {
                if ($a['enabled'] === $b['enabled']) {
                    if ($a['state'] !== $b['state']) {
                        return $a['state'] === 'outdated' ? -1 : 1;
                    }
                    if (isset($a['remote']['time'], $b['remote']['time'])) {
                        return $a['remote']['time'] > $b['remote']['time'] ? -1 : 1;
                    } else {
                        return strcmp($a['remote']['name'], $b['remote']['name']);
                    }
                }
                return $a['enabled'] > $b['enabled'] ? -1 : 1;
            }
            return $a['installed'] > $b['installed'] ? -1 : 1;
        });

        return $this;
    }

    /**
     * @return $this
     */
    protected function _renderLimit()
    {
        $page = $this->getCurPage();
        $size = $this->getPageSize();

        if (!$size || !$page) {
            return $this;
        }

        $offset = ($page - 1) * $size;

        $this->data = array_slice($this->data, $offset, $size);

        return $this;
    }

    /**
     * @param string $field
     * @param [type] $condition
     */
    public function addFieldToFilter($field, $condition)
    {
        return $this->addFilter($field, current($condition));
    }

    /**
     * @param string $field
     * @param string $direction
     */
    public function addOrder($field, $direction)
    {
        return $this->setOrder($field, $direction);
    }
}
