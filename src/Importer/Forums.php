<?php

namespace ArchLinux\ImportFluxBB\Importer;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class Forums
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
        $output->writeln('Importing forums...');

        $forums = $this->database
            ->table($this->fluxBBDatabase . '.forums')
            ->select(
                [
                    'id',
                    'forum_name',
                    'forum_desc',
                    'redirect_url',
                    'moderators',
                    'num_topics',
                    'num_posts',
                    'last_post',
                    'last_post_id',
                    'last_poster',
                    'sort_by',
                    'disp_position',
                    'cat_id'
                ]
            )
            ->orderBy('id')
            ->get()
            ->all();

        $progressBar = new ProgressBar($output, count($forums));

        $this->database->statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($forums as $forum) {
            $this->database
                ->table('tags')
                ->insert(
                    [
                        'id' => $forum->id,
                        'name' => $forum->forum_name,
                        'slug' => Str::slug(preg_replace('/\.+/', '-', $forum->forum_name), '-', 'de'),
                        'description' => $forum->forum_desc,
                        'position' => $forum->disp_position,
                        'parent_id' => $forum->cat_id+CAT_INCREMENT,
                        'discussion_count' => $forum->num_topics,
                        'last_posted_at' => (new \DateTime())->setTimestamp($forum->last_post),
                        'last_posted_discussion_id' => $this->getLastTopicId($forum->last_post_id),
                        'last_posted_user_id' => $this->getLastPostUserId($forum->last_post_id),
                        'color' => '#333'
                    ]
                );
            $progressBar->advance();
        }
        $this->database->statement('SET FOREIGN_KEY_CHECKS=1');
        $progressBar->finish();

        $output->writeln('');
    }

    private function getLastTopicId(?int $lastPostId): ?int
    {
        $topic = $this->database
            ->table($this->fluxBBDatabase . '.posts')
            ->select(['topic_id'])
            ->where('id', '=', $lastPostId)
            ->get()
            ->first();

        return $topic->topic_id ?? null;
    }

    private function getLastPostUserId(?int $lastPostId): ?int
    {
        $topic = $this->database
            ->table($this->fluxBBDatabase . '.posts')
            ->select(['poster_id'])
            ->where('id', '=', $lastPostId)
            ->where('poster_id', '!=', 1)
            ->get()
            ->first();

        return $topic->poster_id ?? null;
    }
}
