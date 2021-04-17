<?php

namespace ArchLinux\ImportFluxBB\Importer;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class Users
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
        $output->writeln('Importing users...');

        $users = $this->database
            ->table($this->fluxBBDatabase . '.users')
            ->select(
                [
                    'id',
                    'group_id',
                    'username',
                    'password',
                    'email',
                    'title',
                    'realname',
                    'url',
                    'jabber',
                    'location',
                    'signature',
                    'disp_topics',
                    'disp_posts',
                    'email_setting',
                    'notify_with_post',
                    'auto_notify',
                    'show_smilies',
                    'show_img',
                    'show_img_sig',
                    'show_avatars',
                    'show_sig',
                    'timezone',
                    'dst',
                    'time_format',
                    'date_format',
                    'language',
                    'style',
                    'num_posts',
                    'last_post',
                    'last_search',
                    'last_email_sent',
                    'last_report_sent',
                    'registered',
                    'registration_ip',
                    'last_visit',
                    'admin_note',
                    'activate_string',
                    'activate_key'
                ]
            )
            ->where('username', '!=', 'Guest')
            ->orderBy('id')
            ->get()
            ->all();

        $progressBar = new ProgressBar($output, count($users));

        $userNames = $this->createUsernameMap($users);

        foreach ($users as $user) {
            $lastSeenAt = (new \DateTime())->setTimestamp($user->last_visit);
            $this->database
                ->table('users')
                ->insert(
                    [
                        'id' => $user->id,
                        'username' => $userNames[$user->id],
                        'nickname' => $user->username,
                        'email' => $user->email,
                        'is_email_confirmed' => $user->group_id == 0 ? 0 : 1,
                        'password' => '', // password will be migrated by migratetoflarum/old-passwords
                        'preferences' => $this->createPreferences($user),
                        'joined_at' => (new \DateTime())->setTimestamp($user->registered),
                        'last_seen_at' => $lastSeenAt,
                        'marked_all_as_read_at' => $lastSeenAt,
                        'read_notifications_at' => null,
                        'discussion_count' => $this->getDiscussionCount($user->id),
                        'comment_count' => $this->getCommentCount($user->id),
                        'read_flags_at' => null,
                        'suspended_until' => null,
                        'migratetoflarum_old_password' => $this->createOldPasswordHash($user->password)
                    ]
                );
            $progressBar->advance();
        }
        $progressBar->finish();

        $output->writeln('');
    }

    private function isValidUsername(string $username): bool
    {
        return preg_match('/^[a-z0-9_-]{3,30}$/i', $username);
    }

    /**
     * See https://github.com/migratetoflarum/old-passwords#sha1-bcrypt
     */
    private function createOldPasswordHash(string $passwordHash): ?string
    {
        $recrypt = true;
        if ($recrypt) {
            $data = [
                'type' => 'sha1-bcrypt',
                'password' => password_hash($passwordHash, PASSWORD_BCRYPT)
            ];
        } else {
            $data = [
                'type' => 'sha1',
                'password' => $passwordHash
            ];
        }

        return json_encode($data) ?? null;
    }

    private function createPreferences($user): ?string
    {
        $preferences = [];

        if ($user->auto_notify) {
            $preferences['followAfterReply'] = true;
        }

        if (!$preferences) {
            return null;
        }

        return json_encode($preferences);
    }

    private function createUsernameMap(array $users): array
    {
        $userNames = [];
        foreach ($users as $user) {
            $userNames[mb_strtolower($user->username)] = ['id' => $user->id, 'username' => $user->username];
        }

        foreach ($userNames as $userNameKey => $userData) {
            $userName = $userData['username'];

            if (!$this->isValidUsername($userName)) {
                $newUserName = preg_replace('/\.+/', '-', $userName);
                $newUserName = trim($newUserName, '-');
                $newUserName = Str::slug($newUserName, '-', 'de');

                if (strlen($newUserName) < 3 || isset($userNames[mb_strtolower($newUserName)])) {
                    $newUserName = ($newUserName ? $newUserName : 'user') . '-' . $userData['id'];
                }

                if (!$this->isValidUsername($newUserName)) {
                    throw new \RuntimeException('Username still invalid: ' . $newUserName);
                }

                unset($userNames[$userNameKey]);
                $userNames[mb_strtolower($newUserName)] = ['id' => $userData['id'], 'username' => $newUserName];
            }
        }

        $userNamesMap = [];
        foreach ($userNames as $userData) {
            $userNamesMap[$userData['id']] = $userData['username'];
        }

        return $userNamesMap;
    }

    private function getDiscussionCount(int $userId): int
    {
        $topics = $this->database
            ->table($this->fluxBBDatabase . '.topics')
            ->join($this->fluxBBDatabase . '.posts', 'topics.first_post_id', '=', 'posts.id')
            ->select('topic_id')
            ->where('posts.poster_id', '=', $userId)
            ->get()
            ->all();
        return count($topics);
    }

    private function getCommentCount(int $userId): int
    {
        $posts = $this->database
            ->table($this->fluxBBDatabase . '.posts')
            ->select('id')
            ->where('poster_id', '=', $userId)
            ->get()
            ->all();
        return count($posts);
    }
}
