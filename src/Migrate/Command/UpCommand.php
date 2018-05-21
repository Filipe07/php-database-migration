<?php

namespace Migrate\Command;

use Migrate\Migration;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpCommand extends AbstractEnvCommand
{
    protected function configure()
    {
        $this
            ->setName('migrate:up')
            ->setDescription('Execute all waiting migration up to [to] option if precised')
            ->addArgument(
                'env',
                InputArgument::REQUIRED,
                'Environment'
            )
            ->addOption(
                'to',
                null,
                InputOption::VALUE_REQUIRED,
                'Migration will be uped up to this migration id included'
            )
            ->addOption(
                'only',
                null,
                InputOption::VALUE_REQUIRED,
                'If you need to up this migration id only'
            )
            ->addOption(
                'changelog-only',
                null,
                InputOption::VALUE_NONE,
                'Mark as applied without executing SQL '
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $env = $input->getArgument('env');
        $this->initEnvironment($env);

        $changeLogOnly = (bool) $input->getOption('changelog-only');
        $toExecute = $this->filterMigrationsToExecute($input, $output);

        if (count($toExecute) == 0) {
            $output->writeln('your database is already up to date');
        } else {
            $progress = new ProgressBar($output, count($toExecute));

            $progress->setFormat(self::$progressBarFormat);
            $progress->setMessage('');
            $progress->start();

            /* @var $migration \Migrate\Migration */
            foreach ($toExecute as $migration) {
                $progress->setMessage($migration->getDescription());
                $this->executeUpMigration($migration, $changeLogOnly);
                $progress->advance();
            }

            $progress->finish();
            $output->writeln('');
        }
    }

    /**
     * @param Migration $migration
     * @param bool      $changeLogOnly
     */
    private function executeUpMigration(Migration $migration, $changeLogOnly = false)
    {
        $this->getDb()->beginTransaction();

        if ($changeLogOnly === false) {
            try {
                $this->getDb()->exec($migration->getSqlUp());
            } catch (\Exception $e) {
                $this->getDb()->rollBack();
                throw new \RuntimeException("migration error, some SQL may be wrong\n\nid: {$migration->getId()}\nfile: {$migration->getFile()}" . $e->getMessage());
            }

            $this->saveToChangelog($migration);
            $this->getDb()->commit();
        }
    }

    public function saveToChangelog(Migration $migration)
    {
        $appliedAt = date('Y-m-d H:i:s');
        $sql = "INSERT INTO changelog
          (id, created_at, applied_at, description)
          VALUES
          ({$migration->getId()},'{$migration->getCreatedAt()}','{$appliedAt}','{$migration->getDescription()}');
        ";

        $result = $this->getDb()->exec($sql);

        if (!$result) {
            throw new \RuntimeException('changelog table has not been initialized');
        }
    }
}
