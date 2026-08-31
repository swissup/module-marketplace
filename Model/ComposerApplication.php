<?php

namespace Swissup\Marketplace\Model;

use Composer\Console\Application;
use Composer\IO\BufferIO;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Composer\ComposerJsonFinder;
use Magento\Framework\Filesystem;
use Swissup\Marketplace\Model\Traits\OutputAware;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ComposerApplication
{
    use OutputAware;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var string
     */
    protected $workdir;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param DirectoryList $directoryList
     * @param ComposerJsonFinder $composerJsonFinder
     * @param Filesystem $filesystem
     */
    public function __construct(
        DirectoryList $directoryList,
        ComposerJsonFinder $composerJsonFinder,
        Filesystem $filesystem
    ) {
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;
        $this->app = new Application();
        $this->app->setAutoExit(false);
        $this->workdir = dirname($composerJsonFinder->findComposerJson());

        putenv('COMPOSER_HOME=' . $directoryList->getPath(DirectoryList::COMPOSER_HOME));

        if ($cacheDir = $this->resolveCacheDir()) {
            putenv('COMPOSER_CACHE_DIR=' . $cacheDir);
        }
    }

    /**
     * @return string|null
     * @see \Composer\Factory::getCacheDir()
     */
    protected function resolveCacheDir()
    {
        if (getenv('COMPOSER_CACHE_DIR')) {
            return null;
        }

        $candidates = $this->getCacheDirCandidates();

        foreach ($candidates as $dir) {
            if (is_dir($dir) && is_writable($dir)) {
                return $dir;
            }
        }

        foreach ($candidates as $dir) {
            if (!is_dir($dir) && is_writable(dirname($dir))) {
                return $dir;
            }
        }

        return null;
    }

    /**
     * @return array
     * @see \Composer\Factory::getCacheDir()
     */
    protected function getCacheDirCandidates()
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $localAppData = getenv('LOCALAPPDATA');

            return $localAppData ? [rtrim(strtr($localAppData, '\\', '/'), '/') . '/Composer'] : [];
        }

        $userDir = getenv('HOME');
        if (!$userDir) {
            return [];
        }

        $userDir = rtrim($userDir, '/');

        if (PHP_OS === 'Darwin') {
            return [$userDir . '/Library/Caches/composer'];
        }

        $dirs = [];

        if ($this->useXdg()) {
            $xdgCache = getenv('XDG_CACHE_HOME') ?: $userDir . '/.cache';
            $dirs[] = rtrim($xdgCache, '/') . '/composer';
        }

        $dirs[] = $userDir . '/.composer/cache';

        return $dirs;
    }

    /**
     * @return boolean
     * @see \Composer\Factory::useXdg()
     */
    protected function useXdg()
    {
        foreach (array_keys($_SERVER) as $key) {
            if (strpos((string) $key, 'XDG_') === 0) {
                return true;
            }
        }

        return is_dir('/etc/xdg');
    }

    /**
     * @param array $command
     * @return string
     * @throws \RuntimeException
     */
    public function run(array $command, ?OutputInterface $output = null)
    {
        $command = $this->normalizeCommand($command);

        if (!$output) {
            $output = $this->getOutput();
        }

        $exitCode = $this->app->run($this->getInput($command), $output);

        $result = '';
        if ($output instanceof BufferedOutput) {
            $result = $output->fetch();
        }

        if ($exitCode) {
            throw new \RuntimeException(
                sprintf('Command "%s" failed: %s', $command['command'], $result)
            );
        }

        return $result;
    }

    /**
     * @param array $params
     * @return string
     */
    public function runAuthCommand(array $params)
    {
        return $this->run(array_merge([
            'command' => 'config',
            '-a' => true,
            '-g' => !$this->canUseRootAuthJson(),
            '-q' => true,
        ], $params));
    }

    /**
     * @return boolean
     */
    public function canUseRootAuthJson()
    {
        if ($this->prepareAuthJsonFile(DirectoryList::ROOT)) {
            return true;
        }

        $this->prepareAuthJsonFile(DirectoryList::COMPOSER_HOME);

        return false;
    }

    /**
     * @return string
     */
    public function getAuthJsonPath()
    {
        if ($this->canUseRootAuthJson()) {
            $dir = $this->directoryList->getPath(DirectoryList::ROOT);
        } else {
            $dir = $this->directoryList->getPath(DirectoryList::COMPOSER_HOME);
        }

        return $dir . '/auth.json';
    }

    /**
     * @param string $directoryCode
     * @return boolean
     */
    protected function prepareAuthJsonFile($directoryCode)
    {
        $directory = $this->filesystem->getDirectoryWrite($directoryCode);

        if (!$directory->isExist('auth.json')) {
            try {
                $directory->writeFile('auth.json', '{}');
            } catch (\Exception $e) {
                return false;
            }
        }

        return $directory->isWritable('auth.json');
    }

    /**
     * @param array $command
     * @return ArrayInput
     */
    protected function getInput(array $command)
    {
        return new ArrayInput(array_merge([
            '--working-dir' => $this->workdir,
        ], $command));
    }

    /**
     * @param array $command
     * @return array
     */
    protected function normalizeCommand(array $command)
    {
        // https://github.com/composer/composer/commit/94df55425596c0137d0aa14485bba2fb05e15de6
        if (version_compare($this->app->getVersion(), '1.8.4', '<')) {
            unset($command['-q']);
        }

        // https://github.com/composer/composer/commit/0e192ced6943a7a6e3de7c52ec0287d115d05784
        if (version_compare($this->app->getVersion(), '1.6.0', '<')) {
            $replace = [
                '--update-with-all-dependencies' => '--update-with-dependencies',
                '--with-all-dependencies' => '--with-dependencies',
            ];

            foreach ($replace as $search => $replace) {
                if (isset($command[$search])) {
                    $command[$replace] = $command[$search];
                    unset($command[$search]);
                }
            }
        }

        return $command;
    }
}
