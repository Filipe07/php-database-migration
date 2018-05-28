<?php

namespace Migrate\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends AbstractEnvCommand
{
    protected function configure()
    {
        $this
            ->setName('migrate:status')
            ->setDescription('Display the current status of the specified environment')
            ->addArgument(
                'env',
                InputArgument::REQUIRED,
                'Environment'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $env = $input->getArgument('env');
        $this->initEnvironment($env);

        $table = new Table($output);
        $table->setHeaders(array('id', 'created_at', 'applied at', 'description'));

        $migrations = $this->getRemoteAndLocalMigrations();

        /* @var $migration Migration */
        foreach ($migrations as $migration) {
            $table->addRow($migration->toArray());
        }

        $table->render();
    }
}
