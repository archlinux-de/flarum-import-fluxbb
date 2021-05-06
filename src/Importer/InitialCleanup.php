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

    public function execute(OutputInterface $output, int $step)
    {
        $output->writeln('Initial cleanup...');

        $this->database->statement('SET FOREIGN_KEY_CHECKS=0');

        if ($step<=1) {
            $this->database->table('users')->truncate();
        }
        if ($step<=2) {
            foreach (glob($this->container[Paths::class]->public . '/assets/avatars/*.*') as $avatar) {
                unlink($avatar);
            }
        }
        if ($step<=4) {
            $this->database->table('tags')->truncate();
            $this->database->table('discussions')->truncate();
            $this->database->table('discussion_tag')->truncate();
        }
        if ($step<=5) {
            $this->database->table('posts')->truncate();
        }
        if ($step<=6) {
            $this->database->table('discussion_user')->truncate();
            $this->database->table('tag_user')->truncate();
        }
        if ($step<=7) {
            $this->database->table('groups')->truncate();
            $this->database->table('group_user')->truncate();
            $this->database->table('group_permission')->truncate();
        }

        $this->database->statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
