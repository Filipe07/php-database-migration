<?php

namespace Migrate\Command;

use Migrate\Config\ConfigLocator;
use Migrate\Utils\ArrayUtil;
use Symfony\Component\Console\Command\Command;

class AbstractCommand extends Command
{
    protected $mainDir;
    protected $environmentDir;
    protected $migrationDir;

    public function checkMigrationToolInit()
    {
        $configLocator = new ConfigLocator(getcwd());
        $conf = $configLocator->locate('migrations.config.yml')->parse();

        $mainDir = ArrayUtil::get($conf, 'main_dir');
        $environmentDir = ArrayUtil::get($conf, 'environment_dir');
        $migrationsDir = ArrayUtil::get($conf, 'migrations_dir');

        if (empty($mainDir) || empty($environmentDir) || empty($migrationsDir)) {
            throw new \RuntimeException("You are not in an initialized php-database-migration tool.\n\nPlease run migrate:init");
        }

        try {
            $this->setMainDir(trim($mainDir, '/'));
            $this->setEnvironmentDir($this->getMainDir() . '/' . trim($environmentDir, '/'));
            $this->setMigrationDir($this->getMainDir() . '/' . trim($migrationsDir, '/'));
        } catch (\Exception $e) {
            throw new \RuntimeException("Php-database-migration tool doesn't seems ok.\n{$e->getMessage()}\n");
        }
    }

    /**
     * @return string
     */
    public function getMainDir()
    {
        return $this->mainDir;
    }

    /**
     * @param string $mainDir
     *
     * @return $this
     */
    public function setMainDir(string $mainDir)
    {
        $this->mainDir = $mainDir;

        return $this;
    }

    /**
     * @return string
     */
    public function getMigrationDir()
    {
        return $this->migrationDir;
    }

    /**
     * @param string $mainDir
     *
     * @return $this
     */
    public function setMigrationDir(string $migrationDir)
    {
        $this->migrationDir = $migrationDir;

        return $this;
    }

    /**
     * @return string
     */
    public function getEnvironmentDir()
    {
        return $this->environmentDir;
    }

    /**
     * @param string $mainDir
     *
     * @return $this
     */
    public function setEnvironmentDir(string $environmentDir)
    {
        $this->environmentDir = $environmentDir;

        return $this;
    }
}
