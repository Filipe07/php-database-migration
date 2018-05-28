<?php

namespace Migrate\Command;

use Cocur\Slugify\Slugify;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class CreateCommand extends AbstractEnvCommand
{
    protected function configure()
    {
        $this
            ->setName('migrate:create')
            ->setDescription('Create a SQL migration');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkMigrationToolInit();

        $questions = $this->getHelperSet()->get('question');

        $descriptionQuestion = new Question('Please enter a description: ');
        $description = $questions->ask($input, $output, $descriptionQuestion);

        $slugger = new Slugify();
        $filename = $slugger->slugify($description);
        $timestamp = time();
        $filename = $timestamp . '_' . $filename . '.sql';

        $templateFile = file_get_contents(__DIR__ . '/../../Templates/migration.tpl');
        $templateFile = str_replace('{DESCRIPTION}', $description, $templateFile);

        $migrationFullPath = $this->getMigrationDir() . '/' . $filename;

        file_put_contents($migrationFullPath, $templateFile);

        $output->writeln("<info>$migrationFullPath created</info>");

        if (!defined('PHPUNIT')) {
            system("vim $migrationFullPath  > `tty`");
        }
    }
}
