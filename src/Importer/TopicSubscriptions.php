<?php

namespace ArchLinux\ImportFluxBB\Importer;

use Illuminate\Database\ConnectionInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class TopicSubscriptions
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
        $output->writeln('Importing topic_subscriptions...');

        $topicSubscriptions = $this->database
            ->table($this->fluxBBDatabase . '.topic_subscriptions')
            ->select(
                [
                    'user_id',
                    'topic_id'
                ]
            )
            ->orderBy('topic_id')
            ->get()
            ->all();

        $progressBar = new ProgressBar($output, count($topicSubscriptions));

        foreach ($topicSubscriptions as $topicSubscription) {
            $this->database
                ->table('discussion_user')
                ->insert(
                    [
                        'user_id' => $topicSubscription->user_id,
                        'discussion_id' => $topicSubscription->topic_id,
                        'last_read_at' => null,
                        'last_read_post_number' => null,
                        'subscription' => 'follow'
                    ]
                );
            $progressBar->advance();
        }
        $progressBar->finish();

        $output->writeln('');
    }
}
