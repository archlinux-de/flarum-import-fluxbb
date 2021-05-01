<?php

namespace ArchLinux\ImportFluxBB\Importer;

use Illuminate\Database\ConnectionInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Validation
{
    private ConnectionInterface $database;

    public function __construct(ConnectionInterface $database)
    {
        $this->database = $database;
    }

    public function execute(OutputInterface $output)
    {
        $output->writeln('Validate data integrity...');

        $this->validateDiscussions($output);
        $this->validateDiscussionTag($output);
        $this->validateDiscussionUser($output);
        $this->validateGroupPermission($output);
        $this->validateGroupUser($output);
        $this->validatePosts($output);
        $this->validatePostMentionsUser($output);
        $this->validateTags($output);
        $this->validateTagUser($output);
    }

    private function assertZero(int $value): void
    {
        if ($value !== 0) {
            throw new \RuntimeException(sprintf('%s is not 0', $value));
        }
    }

    private function validateDiscussions(OutputInterface $output): void
    {
        $output->writeln("\tdiscussions");
        $this->assertZero(
            $this->database
                ->table('discussions')
                ->select('id')
                ->where('user_id', 'NOT IN', '(SELECT id FROM users)')
                ->get()
                ->count()
        );
        $this->assertZero(
            $this->database
                ->table('discussions')
                ->select('id')
                ->where('last_posted_user_id', 'NOT IN', '(SELECT id FROM users)')
                ->get()
                ->count()
        );
        $this->assertZero(
            $this->database
                ->table('discussions')
                ->select('id')
                ->where('first_post_id', 'NOT IN', '(SELECT id FROM posts)')
                ->get()
                ->count()
        );
        $this->assertZero(
            $this->database
                ->table('discussions')
                ->select('id')
                ->where('last_post_id', 'NOT IN', '(SELECT id FROM posts)')
                ->get()
                ->count()
        );
    }

    private function validateDiscussionTag(OutputInterface $output): void
    {
        $output->writeln("\tdiscussion_tag");
        $this->assertZero(
            $this->database
                ->table('discussion_tag')
                ->select('discussion_id')
                ->where('discussion_id', 'NOT IN', '(SELECT id FROM discussions)')
                ->get()
                ->count()
        );
        $this->assertZero(
            $this->database
                ->table('discussion_tag')
                ->select('tag_id')
                ->where('tag_id', 'NOT IN', '(SELECT id FROM tags)')
                ->get()
                ->count()
        );
    }

    private function validateDiscussionUser(OutputInterface $output): void
    {
        $output->writeln("\tdiscussion_user");
        $this->assertZero(
            $this->database
                ->table('discussion_user')
                ->select('discussion_id')
                ->where('discussion_id', 'NOT IN', '(SELECT id FROM discussions)')
                ->get()
                ->count()
        );
        $this->assertZero(
            $this->database
                ->table('discussion_user')
                ->select('user_id')
                ->where('user_id', 'NOT IN', '(SELECT id FROM users)')
                ->get()
                ->count()
        );
    }

    private function validateGroupPermission(OutputInterface $output): void
    {
        $output->writeln("\tgroup_permission");
        $this->assertZero(
            $this->database
                ->table('group_permission')
                ->select('group_id')
                ->where('group_id', 'NOT IN', '(SELECT id FROM groups)')
                ->get()
                ->count()
        );
    }

    private function validateGroupUser(OutputInterface $output): void
    {
        $output->writeln("\tgroup_user");
        $this->assertZero(
            $this->database
                ->table('group_user')
                ->select('group_id')
                ->where('group_id', 'NOT IN', '(SELECT id FROM groups)')
                ->get()
                ->count()
        );
        $this->assertZero(
            $this->database
                ->table('group_user')
                ->select('user_id')
                ->where('user_id', 'NOT IN', '(SELECT id FROM users)')
                ->get()
                ->count()
        );
    }

    private function validatePosts(OutputInterface $output): void
    {
        $output->writeln("\tposts");
        $this->assertZero(
            $this->database
                ->table('posts')
                ->select('discussion_id')
                ->where('discussion_id', 'NOT IN', '(SELECT id FROM discussions)')
                ->get()
                ->count()
        );
        $this->assertZero(
            $this->database
                ->table('posts')
                ->select('user_id')
                ->where('user_id', 'NOT IN', '(SELECT id FROM users)')
                ->get()
                ->count()
        );
        $this->assertZero(
            $this->database
                ->table('posts')
                ->select('edited_user_id')
                ->where('edited_user_id', 'NOT IN', '(SELECT id FROM users)')
                ->get()
                ->count()
        );
    }

    private function validatePostMentionsUser(OutputInterface $output): void
    {
        $output->writeln("\tpost_mentions_user");
        $this->assertZero(
            $this->database
                ->table('post_mentions_user')
                ->select('post_id')
                ->where('post_id', 'NOT IN', '(SELECT id FROM posts)')
                ->get()
                ->count()
        );
        $this->assertZero(
            $this->database
                ->table('post_mentions_user')
                ->select('mentions_user_id')
                ->where('mentions_user_id', 'NOT IN', '(SELECT id FROM users)')
                ->get()
                ->count()
        );
    }

    private function validateTags(OutputInterface $output): void
    {
        $output->writeln("\ttags");
        $this->assertZero(
            $this->database
                ->table('tags')
                ->select('parent_id')
                ->where('parent_id', 'NOT IN', '(SELECT id FROM tags)')
                ->get()
                ->count()
        );
        $this->assertZero(
            $this->database
                ->table('tags')
                ->select('last_posted_discussion_id')
                ->where('last_posted_discussion_id', 'NOT IN', '(SELECT id FROM discussions)')
                ->get()
                ->count()
        );
        $this->assertZero(
            $this->database
                ->table('tags')
                ->select('last_posted_user_id')
                ->where('last_posted_user_id', 'NOT IN', '(SELECT id FROM users)')
                ->get()
                ->count()
        );
    }

    private function validateTagUser(OutputInterface $output): void
    {
        $output->writeln("\ttag_user");
        $this->assertZero(
            $this->database
                ->table('tag_user')
                ->select('user_id')
                ->where('user_id', 'NOT IN', '(SELECT id FROM users)')
                ->get()
                ->count()
        );
        $this->assertZero(
            $this->database
                ->table('tag_user')
                ->select('tag_id')
                ->where('tag_id', 'NOT IN', '(SELECT id FROM tags)')
                ->get()
                ->count()
        );
    }
}
