<?php

namespace Migrate\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class InitCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('migrate:init')
            ->setDescription('Create directories and changelog table on your environment database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questions = $this->getHelperSet()->get('question');

        $mainDirQuestion = new Question('Please enter directory for migration tool <info>(default migrations)</info>: ', 'migrations');
        $mainDir = $questions->ask($input, $output, $mainDirQuestion);

        $envDirQuestion = new Question('Please enter directory environments configs <info>(default environments)</info>: ', 'environments');
        $environmentDir = $questions->ask($input, $output, $envDirQuestion);

        $migrationDirQuestion = new Question('Please enter directory for migration sql data files <info>(default data)</info>: ', 'data');
        $migrationsDir = $questions->ask($input, $output, $migrationDirQuestion);

        // Create directories directories
        try {
            @mkdir(getcwd() . '/' . trim($mainDir, '/'));
            @mkdir(getcwd() . '/' . $mainDir . '/' . trim($environmentDir, '/'));
            @mkdir(getcwd() . '/' . $mainDir . '/' . trim($migrationsDir, '/'));
        } catch (\Exception $e) {
            throw new \RuntimeException('Can\'t create folders!' . $e->getMessage());
        }

        $confTemplate = file_get_contents(__DIR__ . '/../../Templates/config.yml.tpl');
        $confTemplate = str_replace('{MAINDIR}', trim($mainDir, '/'), $confTemplate);
        $confTemplate = str_replace('{ENVDIR}', trim($environmentDir, '/'), $confTemplate);
        $confTemplate = str_replace('{MIGRATIONDIR}', trim($migrationsDir, '/'), $confTemplate);

        file_put_contents(getcwd() . '/migrations.config.yml', $confTemplate);

        $output->writeln('<info>Directories successfully created</info>');
    }
}
