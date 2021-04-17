<?php

namespace ArchLinux\ImportFluxBB\Importer;

use Flarum\Foundation\Paths;
use Illuminate\Database\ConnectionInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitialCleanup
{
    private ConnectionInterface $database;
    private ContainerInterface $container;

    public function __construct(ConnectionInterface $database, ContainerInterface $container)
    {
        $this->database = $database;
        $this->container = $container;
    }

    public function execute(OutputInterface $output)
    {
        $output->writeln('Initial cleanup...');

        $this->database->statement('SET FOREIGN_KEY_CHECKS=0');
        $this->database->statement('TRUNCATE TABLE groups');
        $this->database->statement('TRUNCATE TABLE group_user');
        $this->database->statement('TRUNCATE TABLE tags');
        $this->database->statement('TRUNCATE TABLE users');
        $this->database->statement('SET FOREIGN_KEY_CHECKS=1');

        foreach (glob($this->container[Paths::class]->public . '/assets/avatars/*.*') as $avatar) {
            unlink($avatar);
        }
    }
}
