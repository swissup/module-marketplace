<?php

namespace Swissup\Marketplace\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

use Magento\Framework\App\State;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class InfoCommand extends Command
{

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\State $appState
     */
    public function __construct(State $appState)
    {
        parent::__construct();
        $this->appState = $appState;
    }


    protected function configure()
    {
        $this->setName('marketplace:info')
             ->setDescription('Store environment information');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commands = [
            'php_version' => 'php -v | head -n 1 && whereis php',
            'magento_version' => 'php bin/magento --version',
            'composer_version' => 'composer --version && whereis composer',
            'nginx_apache_user' => 'whoami',
        ];

        $folderPaths = [
            'app/code/Swissup/',
            'app/design/frontend/Swissup/',
            'app/code/Magento/',
            'app/design/frontend/Magento/',
        ];

        try {

            // Environment Check commands

            foreach ($commands as $key => $value) {
                $this->getCommandInfo($input, $output, $value, ucfirst(str_replace('_', ' ', $key)) . ':');
            }

            // Clients Overrides check

            foreach ($folderPaths as $folderPath) {
                $this->checkFolder($folderPath, $output);
            }

            // Output Magento 2 "theme" table data
            $this->outputMagentoThemeData($input, $output);


            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<error>Error: " . $e->getMessage() . "</error>");
            return Command::FAILURE;
        }


        return Command::SUCCESS;
    }

    private function getCommandInfo(InputInterface $input, OutputInterface $output, $command, $description)
    {
        try {
            $result = null;
            $exitCode = null;
            exec($command, $result, $exitCode);

            $output->writeln("<info>$description</info>");


            foreach ($result as $line) {
                $output->writeln("<comment>$line</comment>");
            }
            $output->writeln('_____________________________');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<error>Error running \"$command\" command</error>");

            return Command::FAILURE;
        }
    }

    private function isFolderEmpty($folderPath)
    {
        try {
            $items = scandir($folderPath);
            $items = array_diff($items, ['.', '..']);

            return empty($items);
        } catch (\Throwable $e) {
            throw new \Exception("Unable to check folder contents: " . $e->getMessage());
        }
    }

    private function checkFolder($folderPath, OutputInterface $output)
    {
        try {
            if (!$this->isFolderEmpty($folderPath)) {
                $output->writeln("<error>The folder \"$folderPath\" is not empty.</error>");
            }
        } catch (\Exception $e) {
            // Log or output a message about the skipped folder
            // $output->writeln("<comment>Skipped checking folder \"$folderPath\": " . $e->getMessage() . "</comment>");
        }
    }

    private function outputMagentoThemeData(InputInterface $input, OutputInterface $output)
    {
        $this->initMagento();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $themeCollection = $objectManager->create(\Magento\Theme\Model\ResourceModel\Theme\Collection::class);
        $themes = $themeCollection->getData();

        $io = new SymfonyStyle($input, $output);

        $output->writeln('<info>Magento 2 Theme Table Data:</info>');

        if (empty($themes)) {
            $output->writeln('<comment>No themes found</comment>');
            return;
        }

        // Create a table
        $table = new Table($output);
        $table->setHeaders(['ID', 'Parent ID', 'Theme Title', 'Type']);

        foreach ($themes as $theme) {
            // Determine the style based on the "Type" value
            $style = $theme['type'] == 1 ? 'error' : 'info';

            // Add a row to the table with the specified style
            $table->addRow([
                "<$style>{$theme['theme_id']}</$style>",
                "<$style>{$theme['parent_id']}</$style>",
                "<$style>{$theme['theme_title']}</$style>",
                "<$style>{$theme['type']}</$style>"
            ]);
        }

        // Render the table
        $table->render();
    }

    private function initMagento()
    {
        // Bootstrap Magento to initialize the environment
        try {
            $bootstrap = Bootstrap::create(BP, $_SERVER);
            $objectManager = $bootstrap->getObjectManager();
            $objectManager->get(State::class)->setAreaCode('frontend');
        } catch (LocalizedException | NoSuchEntityException $e) {
            throw new \Exception("Unable to initialize Magento: " . $e->getMessage());
        }
    }
}
