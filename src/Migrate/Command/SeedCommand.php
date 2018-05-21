<?php

namespace Migrate\Command;

use Migrate\Utils\ArrayUtil;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SeedCommand extends AbstractEnvCommand
{
    protected function configure()
    {
        $this
            ->setName('migrate:seed')
            ->setDescription('Seed database based on given file')
            ->addArgument(
                'env',
                InputArgument::REQUIRED,
                'Environment'
            )
            ->addArgument(
                'file',
                InputArgument::OPTIONAL,
                'File'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $env = $input->getArgument('env');
        $this->initEnvironment($env);

        $file = $input->getArgument('file');
        if (empty($file)) {
            $questions = $this->getHelper('question');

            $fileQuestion = new Question('Please enter directory of base file to reset <info>(inside main dir)</info>: ', '');
            $file = $questions->ask($input, $output, $fileQuestion);
        }

        $fileDir = $this->getMainDir() . '/' . $file;
        if (!file_exists($fileDir)) {
            throw new \RuntimeException("Given directory [$fileDir] not exists!");
        }

        $host = ArrayUtil::get($this->config['connection'], 'host');
        $password = ArrayUtil::get($this->config['connection'], 'password');
        $username = ArrayUtil::get($this->config['connection'], 'username');

        $importMysqlStmt = sprintf(
            'mysql --user=%s --password=%s --host=%s -f < %s 2>/dev/null',
            $username,
            $password,
            $host,
            $fileDir
        );

        $output->writeln('Start seeding database...');

        $process = new Process($importMysqlStmt);
        $process->setOptions(array('suppress_errors' => false));
        $process->run();

        if (!$process->isSuccessful()) {
            $exception = new ProcessFailedException($process);
            throw new \RuntimeException("seed database error, some SQL may be wrong\n" . $exception->getMessage());
        }

        $output->writeln('<info>Database reset with success</info>');
    }
}
