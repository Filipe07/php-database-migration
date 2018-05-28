<?php

namespace Migrate\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class AddEnvCommand extends AbstractEnvCommand
{
    protected function configure()
    {
        $this
            ->setName('migrate:addenv')
            ->setDescription('Add an environment to work with php db migrate')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkMigrationToolInit();

        $questions = $this->getHelperSet()->get('question');

        $envQuestion = new Question('Please enter the name of the new environment: ', '');
        $envName = $questions->ask($input, $output, $envQuestion);

        $envConfigFile = $this->getEnvironmentDir() . '/' . $envName . '.yml';
        if (file_exists($envConfigFile)) {
            throw new \InvalidArgumentException("environment [$envName] is already defined!");
        }

        $dbNameQuestion = new Question('Please enter the database name: ', '');
        $dbName = $questions->ask($input, $output, $dbNameQuestion);

        $dbHostQuestion = new Question('Please enter the database host <info>(default localhost)</info>: ', 'localhost');
        $dbHost = $questions->ask($input, $output, $dbHostQuestion);

        $dbPortQuestion = new Question('Please enter the database port <info>(default 3306)</info>: ', '3306');
        $dbPort = $questions->ask($input, $output, $dbPortQuestion);

        $dbUserNameQuestion = new Question('Please enter the database user name: ', '');
        $dbUserName = $questions->ask($input, $output, $dbUserNameQuestion);

        $dbUserPasswordQuestion = new Question('Please enter the database user password: ', '');
        $dbUserPassword = $questions->ask($input, $output, $dbUserPasswordQuestion);

        $changelogTableQuestion = new Question('Please enter the table name for changelog <info>(default changelog)</info>: ', 'changelog');
        $changelogTable = $questions->ask($input, $output, $changelogTableQuestion);

        $confTemplate = file_get_contents(__DIR__ . '/../../Templates/env.yml.tpl');
        $confTemplate = str_replace('{DATABASE}', $dbName, $confTemplate);
        $confTemplate = str_replace('{HOST}', $dbHost, $confTemplate);
        $confTemplate = str_replace('{PORT}', $dbPort, $confTemplate);
        $confTemplate = str_replace('{USERNAME}', $dbUserName, $confTemplate);
        $confTemplate = str_replace('{PASSWORD}', $dbUserPassword, $confTemplate);
        $confTemplate = str_replace('{CHANGELOG}', $changelogTable, $confTemplate);

        //Save configs on files
        file_put_contents($envConfigFile, $confTemplate);

        // Init environment and create database to log changes
        $this->initEnvironment($envName);

        $this->getDb()->exec("
            CREATE TABLE IF NOT EXISTS {$changelogTable}
            (
                id INT PRIMARY KEY,
                created_at varchar(25),
                applied_at varchar(25),
                description varchar(255)
            )
        ");

        $output->writeln("<info>Environment [$envName] created with success!</info>");
    }
}
