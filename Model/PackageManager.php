<?php

namespace Swissup\Marketplace\Model;

use Magento\Framework\Config\File\ConfigFilePool;
use Symfony\Component\Console\Output\OutputInterface;

class PackageManager
{
    /**
     * Package name to module name pairs
     *
     * @var array
     */
    protected $moduleNames = [];

    /**
     * Module name tp pacakge name pairs
     *
     * @var array
     */
    protected $packageNames = [];

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $configValueFactory;

    /**
     * @var \Magento\Framework\Module\PackageInfo
     */
    protected $packageInfo;

    /**
     * @var \Magento\Framework\Module\Status
     */
    protected $moduleStatus;

    /**
     * @var \Magento\Framework\Module\ConflictChecker
     */
    protected $conflictChecker;

    /**
     * @var \Magento\Framework\Module\DependencyChecker
     */
    protected $dependencyChecker;

    /**
     * @var \Magento\Framework\App\DeploymentConfig\Reader
     */
    protected $configReader;

    /**
     * @var \Magento\Framework\App\DeploymentConfig\Writer
     */
    protected $configWriter;

    /**
     * @var \Magento\Theme\Model\Theme\ThemePackageInfo
     */
    protected $themePackageInfo;

    /**
     *@var \Magento\Theme\Model\Theme\ThemeProvider
     */
    protected $themeProvider;

    /**
     * @var \Swissup\Marketplace\Model\ComposerApplication
     */
    protected $composer;

    /**
     * @var \Swissup\Marketplace\Model\PackagesList\Local
     */
    protected $packagesList;

    /**
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param \Magento\Framework\Module\PackageInfo $packageInfo
     * @param \Magento\Framework\Module\Status $moduleStatus
     * @param \Magento\Framework\Module\ConflictChecker $conflictChecker
     * @param \Magento\Framework\Module\DependencyChecker $dependencyChecker
     * @param \Magento\Framework\App\DeploymentConfig\Reader $configReader
     * @param \Magento\Framework\App\DeploymentConfig\Writer $configWriter
     * @param \Magento\Theme\Model\Theme\ThemePackageInfo $themePackageInfo
     * @param \Magento\Theme\Model\Theme\ThemeProvider $themeProvider
     * @param \Swissup\Marketplace\Model\ComposerApplication $composer
     * @param \Swissup\Marketplace\Model\PackagesList\Local $packagesList
     */
    public function __construct(
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Framework\Module\PackageInfo $packageInfo,
        \Magento\Framework\Module\Status $moduleStatus,
        \Magento\Framework\Module\ConflictChecker $conflictChecker,
        \Magento\Framework\Module\DependencyChecker $dependencyChecker,
        \Magento\Framework\App\DeploymentConfig\Reader $configReader,
        \Magento\Framework\App\DeploymentConfig\Writer $configWriter,
        \Magento\Theme\Model\Theme\ThemePackageInfo $themePackageInfo,
        \Magento\Theme\Model\Theme\ThemeProvider $themeProvider,
        \Swissup\Marketplace\Model\ComposerApplication $composer,
        \Swissup\Marketplace\Model\PackagesList\Local $packagesList
    ) {
        $this->cache = $cache;
        $this->configValueFactory = $configValueFactory;
        $this->packageInfo = $packageInfo;
        $this->moduleStatus = $moduleStatus;
        $this->conflictChecker = $conflictChecker;
        $this->dependencyChecker = $dependencyChecker;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->themePackageInfo = $themePackageInfo;
        $this->themeProvider = $themeProvider;
        $this->composer = $composer;
        $this->packagesList = $packagesList;
    }

    /**
     * @param string $package
     * @param OutputInterface|array $options
     * @return string
     * @throws \RuntimeException
     */
    public function show($package, $options = [], OutputInterface $output = null)
    {
        return $this->runComposerCommand([
            'command' => 'show',
            'package' => $package,
            '--no-interaction' => true,
        ], $options, $output);
    }

    /**
     * @param array $packages
     * @param OutputInterface|array $options
     * @param OutputInterface $output
     * @return string
     * @throws \RuntimeException
     */
    public function install($packages, $options = [], OutputInterface $output = null)
    {
        $result = $this->runComposerCommand([
            'command' => 'require',
            'packages' => $packages,
            '--no-progress' => true,
            '--update-with-all-dependencies' => true,
            '--update-no-dev' => $this->getNoDevFlag(),
        ], $options, $output);

        // fix possible issue with virtual theme in DB
        $this->packagesList->isLoaded(false);
        foreach ($this->getThemePaths($packages) as $package => $themePath) {
            if (empty($themePath)) {
                preg_match(
                    '/^(.*)\/theme-(frontend|adminhtml)-(.*)$/',
                    $package,
                    $matches
                );

                if (count($matches) !== 4) {
                    continue;
                }

                list($all, $vendor, $area, $theme) = $matches;
                $themePath = implode('/', [$area, ucfirst($vendor), $theme]);
            }

            $theme = $this->themeProvider->getThemeByFullPath($themePath);
            if ($theme->getId() && $theme->isVirtual()) {
                $theme->setType(\Magento\Theme\Model\Theme::TYPE_PHYSICAL)->save();
            }
        }

        return $result;
    }

    /**
     * @param array $packages
     * @param OutputInterface|array $options
     * @param OutputInterface $output
     * @return string
     * @throws \RuntimeException
     */
    public function uninstall($packages, $options = [], OutputInterface $output = null)
    {
        // collect themes to unset them from config and remove from 'theme' table.
        $themes = [];
        $themeIds = [];
        foreach ($this->getThemePaths($packages) as $themePath) {
            if (empty($themePath)) {
                continue;
            }

            $theme = $this->themeProvider->getThemeByFullPath($themePath);
            $themes[] = $theme;
            $themeIds[] = $theme->getId();
        }

        $result = $this->runComposerCommand([
            'command' => 'remove',
            'packages' => $packages,
            '--no-progress' => true,
            '--update-no-dev' => $this->getNoDevFlag(),
        ], $options, $output);

        if ($themeIds) {
            // Unset config values
            $collection = $this->configValueFactory->create()->getCollection()
                ->addFieldToFilter('path', 'design/theme/theme_id')
                ->addFieldToFilter('value', $themeIds);

            foreach ($collection as $config) {
                $config->delete();
            }

            // clean cache to pass magento validation when deleting a theme
            $this->cache->clean(['config', 'full_page']);

            // Remove themes from DB table
            foreach ($themes as $theme) {
                $theme->delete();
            }
        }

        return $result;
    }

    private function getThemePaths($packages)
    {
        $themes = [];
        $list = $this->packagesList->getList();

        foreach ($packages as $package) {
            if (!isset($list[$package]['type'])) {
                continue;
            }

            if ($list[$package]['type'] === 'metapackage' &&
                !empty($list[$package]['require'])
            ) {
                $themes += $this->getThemePaths(array_keys($list[$package]['require']));
            }

            if ($list[$package]['type'] !== 'magento2-theme') {
                continue;
            }

            $themes[$package] = $this->themePackageInfo->getFullThemePath($package);
        }

        return $themes;
    }

    /**
     * @param array $packages
     * @param OutputInterface|array $options
     * @param OutputInterface $output
     * @return string
     * @throws \RuntimeException
     */
    public function update($packages, $options = [], OutputInterface $output = null)
    {
        return $this->runComposerCommand([
            'command' => 'update',
            'packages' => $packages,
            '--no-progress' => true,
            '--with-all-dependencies' => true,
            '--no-dev' => $this->getNoDevFlag(),
        ], $options, $output);
    }

    /**
     * @param array $packages
     * @return void
     */
    public function disable($packages)
    {
        $this->changeStatus($packages, false);
    }

    /**
     * @param array $packages
     * @return void
     */
    public function enable($packages)
    {
        $this->changeStatus($packages, true);
    }

    protected function changeStatus($packages, $flag)
    {
        if ($flag) {
            $constraints = $this->getConstraintsWhenEnable($packages);
        } else {
            $constraints = $this->getConstraintsWhenDisable($packages);
        }

        if ($constraints['message']) {
            throw new \Exception($constraints['message']);
        }

        $modules = [];
        foreach ($packages as $packageName) {
            $modules[] = $this->getModuleName($packageName);
        }

        $config = $this->configReader->load(ConfigFilePool::APP_CONFIG);
        foreach ($modules as $module) {
            $config['modules'][$module] = (int) $flag;
        }

        $this->configWriter->saveConfig(
            [ConfigFilePool::APP_CONFIG => $config],
            true
        );
    }

    protected function getModuleName($packageName)
    {
        if (isset($this->moduleNames[$packageName])) {
            return $this->moduleNames[$packageName];
        }

        $moduleName = $this->packageInfo->getModuleName($packageName);

        if (!$moduleName) {
            // if module is disabled
            list($vendor, $moduleName) = explode('/', $packageName);
            $moduleName = str_replace('module-', '', $moduleName);
            $moduleName = str_replace('-', '', ucwords($moduleName, '-'));
            $moduleName = ucfirst($vendor) . '_' . $moduleName;
        }

        $this->moduleNames[$packageName] = $moduleName;
        $this->packageNames[$moduleName] = $packageName;

        return $moduleName;
    }

    protected function getPackageName($moduleName)
    {
        if (isset($this->packageNames[$moduleName])) {
            return $this->packageNames[$moduleName];
        }

        $packageName = $this->packageInfo->getPackageName($moduleName);

        if (!$packageName) {
            // if module is disabled
            list($vendor, $packageName) = explode('_', $moduleName);
            $packageName = strtolower(trim(preg_replace('/([A-Z]|[0-9]+)/', "-$1", $packageName), '-'));
            $packageName = lcfirst($vendor) . '/module-' . $packageName;
        }

        $this->packageNames[$moduleName] = $packageName;

        return $this->packageNames[$moduleName];
    }

    public function getConstraintsWhenEnable($packages)
    {
        $modules = [];
        foreach ($packages as $packageName) {
            $modules[] = $this->getModuleName($packageName);
        }

        $message = '';
        $dependencies = $this->prepareConstraints(
            $this->dependencyChecker->checkDependenciesWhenEnableModules($modules)
        );
        $conflicts = $this->prepareConstraints(
            $this->conflictChecker->checkConflictsWhenEnableModules($modules)
        );

        if ($conflicts) {
            $message = __(
                "Cannot enable %1 because it conflicts with %2",
                implode(', ', $packages),
                implode(', ', $conflicts)
            );
        } elseif ($dependencies) {
            $message = __(
                "Cannot enable %1 because it requires the following dependencies: %2",
                implode(', ', $packages),
                implode(', ', $dependencies)
            );
        }

        return [
            'message' => $message,
            'dependencies' => $dependencies,
            'conflicts' => $conflicts,
        ];
    }

    public function getConstraintsWhenDisable($packages)
    {
        $modules = [];
        foreach ($packages as $packageName) {
            $modules[] = $this->getModuleName($packageName);
        }

        $message = '';
        $dependencies = $this->prepareConstraints(
            $this->dependencyChecker->checkDependenciesWhenDisableModules($modules)
        );

        if ($dependencies) {
            $message = __(
                "Cannot disable %1 because other modules uses it: %2",
                implode(', ', $packages),
                implode(', ', $dependencies)
            );
        }

        return [
            'message' => $message,
            'dependencies' => $dependencies,
        ];
    }

    /**
     * @param array $command
     * @param OutputInterface|array $options
     * @param OutputInterface|null $output
     * @return string
     */
    protected function runComposerCommand(array $command, $options = [], OutputInterface $output = null)
    {
        if (!is_array($options)) {
            if ($options instanceof OutputInterface) {
                $output = $options;
            }
            $options = [];
        }

        $formattedOptions = [];
        foreach ($options as $key => $value) {
            $key = strpos($key, '-') === 0 ? $key : '--' . $key;
            $formattedOptions[$key] = $value;
        }

        return $this->composer->run(array_merge($command, $formattedOptions), $output);
    }

    protected function prepareConstraints(array $constraints)
    {
        $result = [];

        foreach ($constraints as $dependencies) {
            foreach ($dependencies as $moduleName => $chain) {
                $packageName = $this->getPackageName($moduleName);
                $result[$packageName] = $packageName;
            }
        }

        return array_values($result);
    }

    /**
     * @return boolean
     */
    protected function getNoDevFlag()
    {
        return !$this->isInstalled('phpunit/phpunit');
    }

    /**
     * @param string $packageName
     * @return boolean
     */
    protected function isInstalled($packageName)
    {
        try {
            $this->show($packageName);
        } catch (\Exception $e) {
            // "Command "show" failed: Package phpunit/phpunit not found in ...
            return false;
        }

        return true;
    }
}
