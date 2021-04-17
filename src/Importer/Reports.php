<?php

namespace ArchLinux\ImportFluxBB\Importer;

use Illuminate\Database\ConnectionInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class Reports
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
        $output->writeln('Importing reports...');

        $reports = $this->database
            ->table($this->fluxBBDatabase . '.reports')
            ->select(
                [
                    'id',
                    'post_id',
                    'topic_id',
                    'forum_id',
                    'reported_by',
                    'created',
                    'message',
                    'zapped',
                    'zapped_by'
                ]
            )
            ->where('post_id', '!=', 0)
            ->where('post_id', 'IN', '(SELECT id FROM fluxbb.posts)')
            ->orderBy('id')
            ->get()
            ->all();

        $progressBar = new ProgressBar($output, count($reports));

        foreach ($reports as $report) {
            $this->database
                ->table('flags')
                ->insert(
                    [
                        'id' => $report->id,
                        'post_id' => $report->post_id,
                        'type' => 'user',
                        'user_id' => $report->reported_by,
                        'reason' => null,
                        'reason_detail' => $report->message,
                        'created_at' => (new \DateTime())->setTimestamp($report->created)
                    ]
                );
            $progressBar->advance();
        }
        $progressBar->finish();

        $output->writeln('');
    }
}
