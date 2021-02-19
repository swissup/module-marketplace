<?php

namespace Swissup\Marketplace\Installer;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Exception\SecurityViolationException;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Simplexml\Config;
use Magento\Framework\Simplexml\ConfigFactory;

class ConfigReader
{
    const DIR = 'etc/marketplace';

    const FILE = 'installer.xml';

    protected $files;

    /**
     * @var string
     */
    protected $currentPath;

    /**
     * @var ComponentRegistrar
     */
    protected $componentRegistrar;

    /**
     * @var ReadFactory
     */
    protected $readDirFactory;

    /**
     * @var Manager
     */
    protected $moduleManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @param ComponentRegistrar $componentRegistrar
     * @param ReadFactory $readDirFactory
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     * @param ConfigFactory $configFactory
     */
    public function __construct(
        ComponentRegistrar $componentRegistrar,
        ReadFactory $readDirFactory,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager,
        ConfigFactory $configFactory
    ) {
        $this->componentRegistrar = $componentRegistrar;
        $this->readDirFactory = $readDirFactory;
        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
        $this->configFactory = $configFactory;
    }

    /**
     * @param array $packages
     * @return boolean
     */
    public function hasConfig($packages)
    {
        foreach ($this->readFiles() as $path => $content) {
            $xml = $this->configFactory->create(['sourceData' => $content]);
            $nodePackages = (array) $xml->getNode('packages/package');

            if (array_intersect($packages, $nodePackages)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function read()
    {
        $output = [
            'rules' => [],      // package/name => [installer_keys]
            'fields' => [],     // installer_key => form data
            'conditions' => [], // installer_key => conditions to check before commands execution
            'commands' => [],   // installer_key => commands to run
        ];

        foreach ($this->readFiles() as $path => $content) {
            $this->currentPath = $path;

            $xml = $this->configFactory->create(['sourceData' => $content]);

            $packages = (array) $xml->getNode('packages/package');
            foreach ($packages as $packageName) {
                $output['rules'][$packageName][] = $path;
            }

            $output['fields'][$path] = $this->parseFields($xml);
            $output['conditions'][$path] = $this->parseConditions($xml);
            $output['commands'][$path] = $this->parseCommands($xml);
        }

        return $output;
    }

    /**
     * @return array
     */
    protected function readFiles()
    {
        if ($this->files !== null) {
            return $this->files;
        }

        $this->files = [];
        $paths = $this->componentRegistrar->getPaths(ComponentRegistrar::MODULE);
        $paths += $this->componentRegistrar->getPaths(ComponentRegistrar::THEME);

        foreach ($paths as $path) {
            $dir = $this->readDirFactory->create($path);
            $filepath = self::DIR . '/' . self::FILE;

            if (!$dir->isReadable($filepath)) {
                continue;
            }

            $this->files[$path] = $dir->readFile($filepath);
        }

        return $this->files;
    }

    /**
     * @param Config $xml
     * @return array
     */
    protected function parseFields(Config $xml)
    {
        $node = $xml->getNode('fields');
        if (!$node) {
            return [];
        }

        $result = [];
        foreach ($node->children() as $field) {
            $name = $field->getAttribute('name');

            if (!isset($result[$name])) {
                $result[$name] = [];
            }

            $result[$name]['title'] = $field->getAttribute('title');

            if (!$field->hasChildren()) {
                continue;
            }

            $options = false;
            $items = $field->descend('option');
            $model = $field->descend('source_model');

            if ($items) {
                $options = [];
                foreach ($items as $item) {
                    $value = (string) $item[0];
                    $options[$value] = [
                        'value' => $value,
                        'label' => $item->getAttribute('title'),
                    ];
                }
            } elseif ($model) {
                $model = (string) $model[0];
                $options = $this->objectManager->get($model)->toOptionArray();
            }

            if ($options !== false) {
                $result[$name]['options'] = $options;
            }
        }

        return $result;
    }

    /**
     * @param Config $xml
     * @return array
     */
    protected function parseConditions(Config $xml)
    {
        $node = $xml->getNode('commands');
        if (!$node || !$node->descend('conditions')) {
            return [];
        }

        $result = [];
        foreach ($node->descend('conditions') as $condition) {
            $result = $this->parseArguments($condition);
        }

        return $result;
    }

    /**
     * @param Config $xml
     * @return array
     */
    protected function parseCommands(Config $xml)
    {
        $node = $xml->getNode('commands');
        if (!$node) {
            return [];
        }

        $commands = [];
        foreach ($node->children() as $child) {
            $tagName = $child->getName();

            if (!in_array($tagName, ['command', 'include'])) {
                continue;
            }

            if ($tagName === 'command') {
                $commands[] = $child;
                continue;
            }

            // read commands from separate file
            $path = $child->getAttribute('path');
            $dir = $this->readDirFactory->create($this->currentPath . '/' . self::DIR);

            if (!$dir->isReadable($path)) {
                continue;
            }

            $xml = $this->configFactory->create([
                'sourceData' => $dir->readFile($path)
            ]);

            foreach ($xml->getNode('command') as $command) {
                $commands[] = $command;
            }
        }

        $result = [];
        foreach ($commands as $i => $command) {
            $class = $command->getAttribute('class');
            $module = $command->getAttribute('module');

            if (!$this->isModuleEnabled($module) || !class_exists($class)) {
                continue;
            }

            $result[$i]['class'] = $class;

            if (!$command->hasChildren() || !$command->descend('data')) {
                continue;
            }

            $result[$i]['data'] = $this->parseArguments(
                $command->descend('data')->children()
            );
        }

        return $result;
    }

    /**
     * @param string $module
     * @return boolean
     */
    protected function isModuleEnabled($module)
    {
        if (!$module) {
            return true;
        }
        return $this->moduleManager->isEnabled($module);
    }

    /**
     * @param \Magento\Framework\Simplexml\Element $node
     * @return array
     * @throws \Exception
     */
    protected function parseArguments(\Magento\Framework\Simplexml\Element $node)
    {
        $i = 0;
        $result = [];

        foreach ($node as $item) {
            $key = $item->getAttribute('name') ?: $i++;
            $helper = $item->getAttribute('helper');

            if (!$item->hasChildren() && !$helper) {
                $value = (string) $item[0];
                $type = (string) $item->getAttribute('type');

                if ($type) {
                    $method = 'prepare' . ucfirst($type);
                    if (method_exists($this, $method)) {
                        $value = $this->{$method}($value);
                    }
                }

                $result[$key] = $value;
                continue;
            }

            $arguments = $this->parseArguments($item->children());

            if ($helper) {
                $result[$key] = [
                    'helper' => $helper,
                    'arguments' => $arguments,
                ];
            } else {
                $result[$key] = $arguments;
            }
        }

        return $result;
    }

    /**
     * @param string $value
     * @return string
     * @throws SecurityViolationException
     */
    private function preparePath($value)
    {
        $subdir = $this->currentPath . '/' . self::DIR . '/';
        $subdir = str_replace('/', DIRECTORY_SEPARATOR, $subdir);
        $result = $subdir . $value;
        $result = realpath($result);

        if (strpos($result, $subdir) !== 0) {
            throw new SecurityViolationException(
                __(
                    'Error during "%1" processing. Relative paths are forbidden: "%2"',
                    $this->currentPath,
                    $value
                )
            );
        }

        return $result;
    }

    /**
     * @param string $value
     * @return mixed
     * @throws RuntimeException
     */
    private function prepareConst($value)
    {
        if (!defined($value)) {
            throw new RuntimeException(
                __('Requested constant is not defined: %1', $value)
            );
        }
        return constant($value);
    }

    /**
     * @param string $value
     * @return int
     */
    private function prepareInt($value)
    {
        return (int) $value;
    }

    /**
     * @param string $value
     * @return boolean
     */
    private function prepareBoolean($value)
    {
        if ($value === 'false') {
            $value = false;
        } else {
            $value = (bool) $value;
        }
        return $value;
    }

    /**
     * @param string $value
     * @return boolean
     */
    private function prepareNull($value)
    {
        return null;
    }
}
