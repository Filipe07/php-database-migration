<?php

namespace Migrate\Command;

use Migrate\Config\ConfigLocator;
use Migrate\Migration;
use Migrate\Utils\ArrayUtil;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractEnvCommand extends AbstractCommand
{
    protected static $progressBarFormat = '%current%/%max% [%bar%] %percent% % [%message%]';

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var \PDO
     */
    protected $db;

    /**
     * @var array
     */
    protected $config;

    /**
     * Get config for environment.
     */
    public function getConfig()
    {
        if (empty($this->config)) {
            $configDirectory = $this->getEnvironmentDir();
            $configLocator = new ConfigLocator($configDirectory);
            $this->config = $configLocator->locate($this->environment . '.yml')->parse();
        }

        return $this->config;
    }

    /**
     * Get database for environment.
     */
    public function getDb()
    {
        if (empty($this->db)) {
            $port = ArrayUtil::get($this->config['connection'], 'port');
            $host = ArrayUtil::get($this->config['connection'], 'host');
            $dbname = ArrayUtil::get($this->config['connection'], 'database');
            $username = ArrayUtil::get($this->config['connection'], 'username');
            $password = ArrayUtil::get($this->config['connection'], 'password');

            $dsn = 'mysql:';
            $dsn .= ($dbname === null) ? '' : "dbname=$dbname;";
            $dsn .= ($host === null) ? '' : "host=$host;";
            $dsn .= ($port === null) ? '' : "port=$port;";

            $this->db = new \PDO(
                $dsn,
                $username,
                $password,
                array(
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                )
            );
        }

        return $this->db;
    }

    /**
     * Function to init environment.
     *
     * @param string $env
     */
    public function initEnvironment($env)
    {
        $this->checkMigrationToolInit();

        try {
            $this->environment = $env;
            $this->config = $this->getConfig();
            $this->db = $this->getDb();
        } catch (\Exception $e) {
            throw new \RuntimeException("Environemt [$env] doesn't seems ok.\n{$e->getMessage()}\n");
        }
    }

    /**
     * @return array(Migration)
     */
    public function getLocalMigrations()
    {
        $fileList = scandir($this->getMigrationDir());
        $fileList = ArrayUtil::filter($fileList);

        $migrations = array();
        foreach ($fileList as $file) {
            $migration = Migration::createFromFile($file, $this->getMigrationDir());
            $migrations[$migration->getId()] = $migration;
        }

        ksort($migrations);

        return $migrations;
    }

    /**
     * @return array(Migration)
     */
    public function getRemoteMigrations()
    {
        $migrations = array();
        $result = $this->getDb()->query("SELECT * FROM {$this->getDb()} ORDER BY id");

        if ($result) {
            foreach ($result as $row) {
                $migration = Migration::createFromRow($row, $this->getMigrationDir());
                $migrations[$migration->getId()] = $migration;
            }

            ksort($migrations);
        }

        return $migrations;
    }

    /**
     * @return array(Migration)
     */
    public function getRemoteAndLocalMigrations()
    {
        $local = $this->getLocalMigrations();
        $remote = $this->getRemoteMigrations();

        foreach ($remote as $aRemote) {
            $local[$aRemote->getId()] = $aRemote;
        }

        ksort($local);

        return $local;
    }

    /**
     * Contains logic to filter migrations to execute.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return array(Migration)
     */
    public function filterMigrationsToExecute(InputInterface $input, OutputInterface $output)
    {
        $toExecute = array();
        if (strpos($this->getName(), 'up') > 0) {
            $toExecute = $this->getToUpMigrations();
        } else {
            $toExecute = $this->getToDownMigrations();
        }

        $only = $input->getOption('only');
        if ($only !== null) {
            if (!array_key_exists($only, $toExecute)) {
                throw new \RuntimeException("Impossible to execute migration $only!");
            }
            $theMigration = $toExecute[$only];
            $toExecute = array($theMigration->getId() => $theMigration);
        }

        $to = $input->getOption('to');
        if ($to !== null) {
            if (!array_key_exists($to, $toExecute)) {
                throw new \RuntimeException("Target migration $to does not exist or has already been executed/downed!");
            }

            $temp = $toExecute;
            $toExecute = array();
            foreach ($temp as $migration) {
                $toExecute[$migration->getId()] = $migration;
                if ($migration->getId() == $to) {
                    break;
                }
            }
        }

        return $toExecute;
    }

    private function getToUpMigrations()
    {
        $locales = $this->getLocalMigrations();
        $remotes = $this->getRemoteMigrations();

        return array_diff_key($locales, $remotes);
    }

    private function getToDownMigrations()
    {
        $remotes = $this->getRemoteMigrations();

        return array_reverse($remotes, true);
    }
}
