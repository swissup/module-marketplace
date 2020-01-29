<?php

namespace Swissup\Marketplace\Model\Installer;

class Installer
{
    /**
     * @var array
     */
    private $data;

    /**
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param Config\Reader $configReader
     * @param RequestFactory $requestFactory
     */
    public function __construct(
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        Config\Reader $configReader,
        RequestFactory $requestFactory
    ) {
        $this->cache = $cache;
        $this->objectManager = $objectManager;
        $this->configReader = $configReader;
        $this->requestFactory = $requestFactory;
    }

    /**
     * Run packages installation with optional request data.
     *
     * @param array $packages
     * @param array $requestData
     * @return void
     */
    public function run(array $packages, array $requestData = [])
    {
        $request = $this->requestFactory->create(['data' => $requestData]);

        foreach ($this->getCommands($packages, $request) as $config) {
            $params = [];
            foreach ($config['data'] as $key => $param) {
                $params[$key] = $this->processArguments($param);
            }

            $request->setParams($params);

            $this->objectManager
                ->get($config['class'])
                ->execute($request);
        }

        $this->cache->clean([
            'block_html',
            'config',
            'full_page',
            'layout',
            'translate',
        ]);
    }

    /**
     * @param mixed $packages
     * @return boolean
     */
    public function hasInstaller($packages)
    {
        if (!is_array($packages)) {
            $packages = [$packages];
        }

        $flag = false;

        foreach ($packages as $package) {
            $flag = (bool) $this->getInstaller($package);
            if ($flag) {
                break;
            }
        }

        return $flag;
    }

    /**
     * @param array $packages
     * @return array
     */
    public function getFormConfig($packages)
    {
        $result = [];

        foreach ($this->getInstallers($packages) as $installer) {
            $fields = $this->data['fields'][$installer] ?? [];
            $result = array_replace_recursive($result, $fields);
        }

        return $result;
    }

    /**
     * @param string $package
     * @return array
     */
    private function getInstaller($package)
    {
        $this->load();

        return $this->data['rules'][$package] ?? [];
    }

    /**
     * @param array $packages
     * @return array
     */
    private function getInstallers($packages)
    {
        $result = [];

        foreach ($packages as $package) {
            $installer = $this->getInstaller($package);

            if (!$installer) {
                continue;
            }

            $result = array_merge($result, $installer);
        }

        return array_unique($result);
    }

    /**
     * @param array $packages
     * @param Request $request
     * @return array
     */
    private function getCommands($packages, Request $request)
    {
        $result = [];

        foreach ($this->getInstallers($packages) as $installer) {
            $requirements = $this->data['conditions'][$installer] ?? [];
            foreach ($requirements as $param => $value) {
                if ($request->getData($param) != $value) {
                    // customer didn't select this package in the form. Another argento theme, for example.
                    continue 2;
                }
            }

            $commands = $this->data['commands'][$installer] ?? [];
            $result = array_replace_recursive($result, $commands);
        }

        return $result;
    }

    /**
     * @param array $arguments
     * @return array
     */
    private function processArguments($arguments)
    {
        if (!is_array($arguments)) {
            return $arguments;
        }

        // start from the deepest nested helper
        foreach ($arguments as $key => $value) {
            if (is_array($value)) {
                $arguments[$key] = $this->processArguments($value);
            }
        }

        if (isset($arguments['helper'], $arguments['arguments']) &&
            strpos($arguments['helper'], '::') !== false
        ) {
            list($class, $method) = explode('::', $arguments['helper']);

            return call_user_func_array(
                [$this->objectManager->get($class), $method],
                $arguments['arguments']
            );
        }

        return $arguments;
    }

    /**
     * @return void
     */
    private function load()
    {
        if ($this->data !== null) {
            return;
        }

        $this->data = $this->configReader->read();
    }
}
