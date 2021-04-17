<?php

namespace ArchLinux\ImportFluxBB\Importer;

use Illuminate\Database\ConnectionInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class ForumSubscriptions
{
    private ConnectionInterface $database;
    private string $fluxBBDatabase;

    public function __construct(ConnectionInterface $database)
    {
        $this->database = $database;
    }

    public function execute(OutputInterface $output, string $fluxBBDatabase)
    {
        $this->fluxBBDatabase = $fluxBBDatabase;
        $output->writeln('Importing forum_subscriptions...');

        $topicSubscriptions = $this->database
            ->table($this->fluxBBDatabase . '.forum_subscriptions')
            ->select(
                [
                    'user_id',
                    'forum_id'
                ]
            )
            ->orderBy('forum_id')
            ->get()
            ->all();

        $progressBar = new ProgressBar($output, count($topicSubscriptions));

        foreach ($topicSubscriptions as $topicSubscription) {
            $this->database
                ->table('tag_user')
                ->insert(
                    [
                        'user_id' => $topicSubscription->user_id,
                        'tag_id' => $topicSubscription->forum_id,
                        'marked_as_read_at' => null,
                        'is_hidden' => 0
                    ]
                );
            $progressBar->advance();
        }
        $progressBar->finish();

        $output->writeln('');
    }
}
