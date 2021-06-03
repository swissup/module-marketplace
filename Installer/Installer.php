<?php

namespace Swissup\Marketplace\Installer;

use Swissup\Marketplace\Model\Traits\LoggerAware;

class Installer
{
    use LoggerAware;

    /**
     * @var array
     */
    private $data;

    /**
     * @var \Magento\Framework\App\Cache\Manager
     */
    private $cache;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @param \Magento\Framework\App\Cache\Manager $cache
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param ConfigReader $configReader
     * @param RequestFactory $requestFactory
     */
    public function __construct(
        \Magento\Framework\App\Cache\Manager $cache,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        ConfigReader $configReader,
        RequestFactory $requestFactory
    ) {
        $this->cache = $cache;
        $this->appState = $appState;
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

        try {
            $this->appState->getAreaCode();
        } catch (\Exception $e) {
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        }

        foreach ($this->getInstallers($packages) as $installer) {
            $requirements = $this->data['conditions'][$installer] ?? [];
            foreach ($requirements as $param => $value) {
                if ($request->getData($param) != $value) {
                    // customer didn't select this package in the form.
                    // Another argento theme, for example.
                    continue 2;
                }
            }

            $info = array_slice(explode('/', $installer), -2, 2);
            $this->getLogger()->notice(sprintf('Processing %s', implode('/', $info)));

            $commands = $this->data['commands'][$installer] ?? [];

            foreach ($commands as $config) {
                $params = [];
                $data = isset($config['data']) ? $config['data'] : [];
                foreach ($data as $key => $param) {
                    $params[$key] = $this->processArguments($param, $requestData);
                }

                $request->setParams($params);

                $command = $this->objectManager->get($config['class']);

                if (method_exists($command, 'setLogger')) {
                    $command->setLogger($this->getLogger());
                }

                $command->execute($request);
            }
        }

        $this->cache->clean(array_intersect([
            'block_html',
            'config',
            'full_page',
            'layout',
            'translate',
            'compiled_config',
        ], $this->cache->getAvailableTypes()));
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

        return $this->configReader->hasConfig($packages);
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
     * @param array $arguments
     * @param array $requestData
     * @return array
     */
    private function processArguments($arguments, $requestData)
    {
        if (!is_array($arguments)) {
            return $arguments;
        }

        // start from the deepest nested helper
        foreach ($arguments as $key => $value) {
            if (is_array($value)) {
                $arguments[$key] = $this->processArguments($value, $requestData);
            }
        }

        if (isset($arguments['helper'], $arguments['arguments']) &&
            strpos($arguments['helper'], '::') !== false
        ) {
            list($class, $method) = explode('::', $arguments['helper']);

            return call_user_func_array(
                [$this->objectManager->get($class), $method],
                ['request' => $requestData] + array_values($arguments['arguments'])
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
