<?php

namespace Swissup\Marketplace\Model\PackagesList;

class Remote extends AbstractList
{
    /**
     * @var \Swissup\Marketplace\Model\ChannelRepository
     */
    private $channelRepository;

    /**
     * @var string
     */
    private $channelId;

    /**
     * @param \Swissup\Marketplace\Model\ChannelRepository $channelRepository
     */
    public function __construct(
        \Swissup\Marketplace\Model\ChannelRepository $channelRepository
    ) {
        $this->channelRepository = $channelRepository;
    }

    /**
     * @param mixed $id
     * @return $this
     */
    public function setChannelId($id)
    {
        $this->channelId = $id;

        return $this;
    }

    /**
     * @param string|null $id
     * @return $this
     */
    public function getChannelId()
    {
        return $this->channelId;
    }

    /**
     * @return array
     */
    public function getList()
    {
        if ($this->isLoaded()) {
            return $this->data;
        }

        $channels = [];

        if ($this->getChannelId()) {
            try {
                $channels = [$this->channelRepository->getById($this->getChannelId())];
            } catch (\Exception $e) {
                //
            }
        }

        if (!$channels) {
            $channels = $this->channelRepository->getList(true);
        }

        foreach ($channels as $channel) {
            try {
                $this->processChannel($channel);
            } catch (\Exception $e) {
                //
            }
        }

        $this->isLoaded(true);

        return $this->data;
    }

    private function processChannel($channel)
    {
        $packages = $channel->getPackages();

        foreach ($packages as $id => $packageData) {
            if (!isset($this->data[$id]['channels'])) {
                $this->data[$id]['channels'] = [];
            }
            $this->data[$id]['channels'][] = $channel->getIdentifier();

            $latestVersion = $this->getLatestVersion($packageData);

            if (isset($this->data[$id]['version']) &&
                version_compare($this->data[$id]['version'], $latestVersion, '>=')
            ) {
                // this channel has older version in the list - skip it
                continue;
            }

            $this->data[$id] = array_replace(
                $this->data[$id],
                $this->extractPackageData($packageData[$latestVersion])
            );

            if (!empty($packageData['dev-master']['extra']['marketplace'])) {
                $data = $this->extractPackageData($packageData['dev-master']);
                $this->data[$id]['marketplace'] = $data['marketplace'];
            }

            $this->data[$id]['marketplace'] = $this->extractMarketplaceData(
                $id,
                $this->data[$id],
                $packages
            );

            $this->data[$id]['uniqid'] = $channel->getIdentifier() . ':' . $id;
            foreach ($packageData as $version => $data) {
                $this->data[$id]['versions'][$version] = $this->extractPackageData($data);
            }
        }
    }

    /**
     * @param string $packageName
     * @param array $packageData
     * @param array $allPackages
     * @return array
     */
    private function extractMarketplaceData($packageName, $packageData, $allPackages)
    {
        // ability to copy some other module's data.
        if (isset($packageData['marketplace']['@extends'])) {
            $parentPackage = $packageData['marketplace']['@extends'];

            if (isset($allPackages[$parentPackage]['dev-master']['extra'])) {
                $data = $this->extractPackageData($allPackages[$parentPackage]['dev-master']);
                $data = $data['marketplace'];
            }

            $packageData['marketplace'] = array_merge(
                $data['marketplace'],
                $packageData['marketplace']
            );
            unset($packageData['marketplace']['@extends']);

            return $packageData['marketplace'];
        }

        if (!empty($packageData['marketplace'])) {
            return $packageData['marketplace'];
        }

        if (!isset($packageData['type']) || $packageData['type'] !== 'metapackage') {
            return [];
        }

        // When processing metapackage named as "vendor/package" or "vendor/package-metapackage",
        // try to get marketplace data from:
        //  - vendor/package [optional]
        //  - vendor/module-package
        //  - vendor/theme-frontend-package
        //  - vendor/theme-adminhtml-package

        list($vendor, $name) = explode('/', $packageName);
        $parentPackages = [];

        if (strpos($name, '-metapackage') !== false) {
            $name = str_replace('-metapackage', '', $name);
            $parentPackages[] = $vendor . '/' . $name;
        } elseif (strpos($name, 'product-') !== false) {
            $name = str_replace('product-', '', $name);
            $parentPackages[] = $vendor . '/' . $name;
        }

        array_push(
            $parentPackages,
            $vendor . '/module-' . $name,
            $vendor . '/theme-frontend-' . $name,
            $vendor . '/theme-adminhtml-' . $name
        );

        foreach ($parentPackages as $parentPackage) {
            if (!isset($allPackages[$parentPackage]['dev-master']['extra'])) {
                continue;
            }

            $data = $this->extractPackageData($allPackages[$parentPackage]['dev-master']);

            if (isset($data['marketplace'])) {
                return $data['marketplace'];
            }
        }

        return [];
    }

    /**
     * @param array $data
     * @return string
     */
    private function getLatestVersion($data)
    {
        return array_reduce(array_keys($data), function ($carry, $item) {
            if (version_compare($carry, $item) === -1) {
                $carry = $item;
            }
            return $carry;
        });
    }
}
