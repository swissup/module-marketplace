<?php

namespace Swissup\Marketplace\Model;

use Composer\Console\Application;
use Composer\IO\BufferIO;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Composer\ComposerJsonFinder;
use Swissup\Marketplace\Model\Traits\OutputAware;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ComposerApplication
{
    use OutputAware;

    /**
     * @var \Composer\Console\Application
     */
    protected $app;

    /**
     * @var string
     */
    protected $workdir;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @param DirectoryList $directoryList
     * @param ComposerJsonFinder $composerJsonFinder
     */
    public function __construct(
        DirectoryList $directoryList,
        ComposerJsonFinder $composerJsonFinder
    ) {
        $this->app = new Application();
        $this->app->setAutoExit(false);
        $this->workdir = dirname($composerJsonFinder->findComposerJson());

        putenv('COMPOSER_HOME=' . $directoryList->getPath(DirectoryList::COMPOSER_HOME));
    }

    /**
     * @param array $command
     * @return string
     * @throws \RuntimeException
     */
    public function run(array $command, OutputInterface $output = null)
    {
        $this->app->resetComposer();

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
     * @param array $command
     * @return string
     */
    public function runAuthCommand(array $command)
    {
        return $this->run(array_merge([
            'command' => 'config',
            '-a' => true,
            '-g' => true,
        ], $command));
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
