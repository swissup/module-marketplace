<?php

namespace Swissup\Marketplace\Model\ResourceModel\Package;

use Magento\Framework\Data\Collection\EntityFactoryInterface;

class Collection extends \Magento\Framework\Data\Collection
{
    /**
     * @var string
     */
    protected $_itemObjectClass = \Swissup\Marketplace\Model\Package::class;

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

        $localPackages = $this->localPackages->getList();
        $remotePackages = $this->remotePackages->getList();

        foreach ($remotePackages as $id => $remoteData) {
            if (!empty($remoteData['marketplace']['hidden']) ||
                !empty($localPackages[$id]['marketplace']['hidden'])
            ) {
                continue;
            }

            // if ($data['type'] !== 'magento2-module') {
            //     continue;
            // }

            $localVersion = $localPackages[$id]['version'] ?? false;

            $this->data[$id] = [
                'name' => $id,
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

        usort($this->data, [$this, '_sortPackages']);

        foreach ($this->data as $values) {
            $item = $this->getNewEmptyItem();
            $item->setData($values);
            $item->setId($values['name']);

            $this->addItem($item);
        }

        $this->_setIsLoaded(true);

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
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function _sortPackages($a, $b)
    {
        if ($a['installed'] === $b['installed']) {
            if ($a['enabled'] === $b['enabled']) {
                if ($a['state'] !== $b['state']) {
                    return $a['state'] === 'outdated' ? -1 : 1;
                }
                return $a['remote']['time'] > $b['remote']['time'] ? -1 : 1;
            }
            return $a['enabled'] > $b['enabled'] ? -1 : 1;
        }
        return $a['installed'] > $b['installed'] ? -1 : 1;
    }

    public function addFieldToFilter($field, $condition)
    {
        return $this;
    }

    /**
     * Compatibility with Ui/DataProvider
     *
     * @param string $field
     * @param string $direction
     */
    public function addOrder($field, $direction)
    {
        return $this->setOrder($field, $direction);
    }
}
