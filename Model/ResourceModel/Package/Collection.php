<?php

namespace Swissup\Marketplace\Model\ResourceModel\Package;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Data\Collection\EntityFactoryInterface;

class Collection extends \Magento\Framework\Data\Collection
{
    /**
     * Item object class name
     *
     * @var string
     */
    protected $_itemObjectClass = \Swissup\Marketplace\Model\Package::class;

    /**
     * Array of packages received from remote server.
     *
     * @var array
     */
    private $data = [];

    public function __construct(
        EntityFactoryInterface $entityFactory,
        \Magento\Framework\Module\Dir\Reader $moduleDirReader,
        \Magento\Framework\Component\ComponentRegistrarInterface $registrar,
        \Magento\Framework\Json\DecoderInterface $jsonDecoder,
        \Magento\Framework\Filesystem\Driver\File $filesystemDriver,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
    ) {
        $this->moduleDirReader = $moduleDirReader;
        $this->registrar = $registrar;
        $this->jsonDecoder = $jsonDecoder;
        $this->filesystemDriver = $filesystemDriver;
        $this->request = $request;
        $this->jsonHelper = $jsonHelper;
        $this->httpClientFactory = $httpClientFactory;

        return parent::__construct($entityFactory);
    }

    /**
     * @return array
     */
    private function getRemoteUrls()
    {
        return [
            'https://swissup.github.io/packages/packages.json',
            // 'https://ci.swissuplabs.com/api/packages.json',
        ];
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


        $components = [
            ComponentRegistrar::THEME,
            ComponentRegistrar::MODULE,
        ];

        $localModules = [];
        $enabledModules = $this->moduleDirReader->getComposerJsonFiles()->toArray();
        foreach ($components as $component) {
            $paths = $this->registrar->getPaths($component);
            foreach ($paths as $name => $path) {
                $path = $path . '/composer.json';

                try {
                    $config = $this->filesystemDriver->fileGetContents($path);
                    $config = $this->jsonDecoder->decode($config);
                    $localModules[$config['name']] = $this->extractPackageData($config);
                    $localModules[$config['name']]['enabled'] =
                        $component === ComponentRegistrar::THEME || // themes are always enabled
                        !empty($enabledModules[$path]);
                } catch (\Exception $e) {
                    // skip module with broken composer.json file
                }
            }
        }

        $remoteModules = [];
        foreach ($this->getRemoteUrls() as $remoteUrl) {
            $packages = $this->fetchPackages($remoteUrl);

            if (!$packages) {
                continue;
            }

            foreach ($packages as $id => $packageData) {
                $versions = array_keys($packageData);
                $latestVersion = array_reduce($versions, function ($carry, $item) {
                    if (version_compare($carry, $item) === -1) {
                        $carry = $item;
                    }
                    return $carry;
                });

                if (isset($localModules[$id]['version']) &&
                    isset($packageData[$localModules[$id]['version']])
                ) {
                    $localModules[$id]['time'] = $packageData[$localModules[$id]['version']]['time'];
                }

                $remoteModules[$id] = $this->extractPackageData($packageData[$latestVersion]);
                $remoteModules[$id]['versions'] = $versions;
            }
        }

        foreach ($remoteModules as $id => $data) {
            if (!empty($data['marketplace']['hidden']) ||
                !empty($localModules[$id]['marketplace']['hidden'])
            ) {
                continue;
            }

            if ($data['type'] !== 'magento2-module') {
                continue;
            }

            $this->data[$id] = [
                'name' => $id,
                'description' => $data['description'] ?? '',
                'image_src' => $data['marketplace']['gallery'][0] ??
                    ($localModules[$id]['marketplace']['gallery'][0] ?? false),
                'keywords' => $data['keywords'] ?? [],
                'version' => $localModules[$id]['version'] ?? false,
                'time' => $localModules[$id]['time'] ?? false,
                'installed' => isset($localModules[$id]),
                'enabled' => $localModules[$id]['enabled'] ?? false,
                'remote' => $data,
                'local' => $localModules[$id] ?? false,
            ];

            if (!$this->data[$id]['version']) {
                $code = 'na';
            } else {
                $updated = version_compare($this->data[$id]['version'], $data['version'], '>=');
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
     * @param array $data
     * @return array
     */
    protected function extractPackageData(array $data)
    {
        $result = array_intersect_key($data, array_flip([
            'name',
            'description',
            'keywords',
            'version',
            'require',
            'time',
            'type',
        ]));

        $result['marketplace'] = $data['extra']['swissup'] ?? [];
        if (isset($data['extra']['marketplace'])) {
            $result['marketplace'] = $data['extra']['marketplace'];
        }

        return $result;
    }

    /**
     * Sort items to show outdated on the top, not installed at the bottom.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function _sortPackages($a, $b)
    {
        if ($a['installed'] === $b['installed']) {
            if ($a['state'] !== $b['state']) {
                return $a['state'] === 'outdated' ? -1 : 1;
            }
            return $a['remote']['time'] > $b['remote']['time'] ? -1 : 1;
        }

        return $a['installed'] > $b['installed'] ? -1 : 1;
    }

    /**
     * Fetch packages from remote server.
     *
     * @param string $url
     * @return array|false
     */
    protected function fetchPackages($url)
    {
        $response = [];

        try {
            $response = $this->fetch($url);
            $response = $this->jsonHelper->jsonDecode($response);
        } catch (\Exception $e) {
            return false;
        }

        if (!is_array($response)) {
            return false;
        }

        if (isset($response['includes'])) {
            $url = substr($url, 0, strrpos($url, '/') + 1);

            try {
                $response = $this->fetch($url . key($response['includes']));
                $response = $this->jsonHelper->jsonDecode($response);
            } catch (\Exception $e) {
                return false;
            }

            if (!is_array($response)) {
                return false;
            }
        }

        return $response['packages'] ?? false;
    }

    /**
     * @param  string $url
     * @return string
     */
    protected function fetch($url)
    {
        $client = $this->httpClientFactory->create();
        $client->setUri($url);
        $client->setConfig([
            'maxredirects' => 5,
            'timeout' => 30
        ]);
        $client->setParameterGet('domain', $this->request->getHttpHost());
        return $client->request()->getBody();
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
