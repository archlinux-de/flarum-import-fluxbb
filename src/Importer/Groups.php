<?php

namespace ArchLinux\ImportFluxBB\Importer;

use Illuminate\Database\ConnectionInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class Groups
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
        $this->importGroups($output);
        $this->importUserGroup($output);
    }

    private function importGroups(OutputInterface $output): void
    {
        $output->writeln('Importing groups...');

        $groups = $this->database
            ->table($this->fluxBBDatabase . '.groups')
            ->select(
                [
                    'g_id',
                    'g_title',
                    'g_user_title',
                    'g_promote_min_posts',
                    'g_promote_next_group',
                    'g_moderator',
                    'g_mod_edit_users',
                    'g_mod_rename_users',
                    'g_mod_change_passwords',
                    'g_mod_ban_users',
                    'g_mod_promote_users',
                    'g_read_board',
                    'g_view_users',
                    'g_post_replies',
                    'g_post_topics',
                    'g_edit_posts',
                    'g_delete_posts',
                    'g_delete_topics',
                    'g_post_links',
                    'g_set_title',
                    'g_search',
                    'g_search_users',
                    'g_send_email',
                    'g_post_flood',
                    'g_search_flood',
                    'g_email_flood',
                    'g_report_flood'
                ]
            )
            ->orderBy('g_id')
            ->get()
            ->all();

        $progressBar = new ProgressBar($output, count($groups));

        foreach ($groups as $group) {
            $newGroupId = $this->mapToNewGroupId($group->g_id);
            $this->database
                ->table('groups')
                ->insert(
                    [
                        'id' => $newGroupId,
                        'name_singular' => $group->g_user_title ?? $group->g_title,
                        'name_plural' => $group->g_title,
                        'color' => $this->getGroupColor($newGroupId),
                        'icon' => $this->getGroupIcon($newGroupId),
                        'is_hidden' => 0
                    ]
                );
            $progressBar->advance();
        }

        foreach ($groups as $group) {
            $this->importGroupPermissions($group->g_id);
        }

        $progressBar->finish();

        $output->writeln('');
    }

    private function importUserGroup(OutputInterface $output): void
    {
        $output->writeln('Importing user.group_id...');

        $users = $this->database
            ->table($this->fluxBBDatabase . '.users')
            ->select(
                [
                    'id',
                    'group_id'
                ]
            )
            ->where('username', '!=', 'Guest')
            ->where('group_id', '!=', 4)
            ->where('group_id', '!=', 0)
            ->orderBy('id')
            ->get()
            ->all();

        $progressBar = new ProgressBar($output, count($users));

        foreach ($users as $user) {
            $this->database
                ->table('group_user')
                ->insert(
                    [
                        'user_id' => $user->id,
                        'group_id' => $this->mapToNewGroupId($user->group_id),
                    ]
                );
            $progressBar->advance();
        }
        $progressBar->finish();

        $output->writeln('');
    }

    private function mapToNewGroupId(int $oldId): int
    {
        $groupIdMap = [
            1 => 1,
            2 => 4,
            4 => 3,
            3 => 2
        ];

        return isset($groupIdMap[$oldId]) ? $groupIdMap[$oldId] : $oldId;
    }

    private function getGroupColor(int $groupId): ?string
    {
        $colors = [
            1 => '#B72A2A',
            2 => null,
            3 => null,
            4 => '#80349E'
        ];
        return isset($colors[$groupId]) ? $colors[$groupId] : null;
    }

    private function getGroupIcon(int $groupId): ?string
    {
        $icons = [
            1 => 'fas fa-wrench',
            2 => null,
            3 => null,
            4 => 'fas fa-bolt'
        ];
        return isset($icons[$groupId]) ? $icons[$groupId] : null;
    }

    private function importGroupPermissions(int $oldGroupId): void
    {
        $forumPermissions = $this->database
            ->table($this->fluxBBDatabase . '.forum_perms')
            ->select(
                [
                    'group_id',
                    'forum_id',
                    'read_forum',
                    'post_replies',
                    'post_topics'
                ]
            )
            ->where('group_id', '=', $oldGroupId)
            ->orderBy('forum_id')
            ->get()
            ->all();

        foreach ($forumPermissions as $forumPermission) {
            $this->database
                ->table('tags')
                ->where('id', '=', $forumPermission->forum_id)
                ->update(
                    [
                        'is_restricted' => 1
                    ]
                );
            $forumPermission->group_id = $this->mapToNewGroupId($forumPermission->group_id);
            $this->insertPermission($forumPermission, 'read_forum', 'viewDiscussions');

            // Disable write permissions for guests
            if ($forumPermission->group_id !== 2) {
                $this->insertPermission($forumPermission, 'post_replies', 'discussion.reply');
                $this->insertPermission($forumPermission, 'post_replies', 'discussion.replyWithoutApproval');

                $this->insertPermission($forumPermission, 'post_topics', 'startDiscussion');
                $this->insertPermission($forumPermission, 'post_topics', 'discussion.startWithoutApproval');
            }
        }
    }

    private function insertPermission($forumPermission, string $oldPermission, string $newPermission): void
    {
        if ($forumPermission->{$oldPermission}) {
            if ($forumPermission->group_id !== 1) {
                $this->database
                    ->table('group_permission')
                    ->insert(
                        [
                            'group_id' => $forumPermission->group_id,
                            'permission' => 'tag' . $forumPermission->forum_id . '.' . $newPermission,
                        ]
                    );
            }
        }
    }
}
