<?php

namespace ArchLinux\ImportFluxBB\Console;

use ArchLinux\ImportFluxBB\Importer\Avatars;
use ArchLinux\ImportFluxBB\Importer\Bans;
use ArchLinux\ImportFluxBB\Importer\Categories;
use ArchLinux\ImportFluxBB\Importer\Forums;
use ArchLinux\ImportFluxBB\Importer\ForumSubscriptions;
use ArchLinux\ImportFluxBB\Importer\Groups;
use ArchLinux\ImportFluxBB\Importer\InitialCleanup;
use ArchLinux\ImportFluxBB\Importer\PostMentionsUser;
use ArchLinux\ImportFluxBB\Importer\Posts;
use ArchLinux\ImportFluxBB\Importer\Reports;
use ArchLinux\ImportFluxBB\Importer\Topics;
use ArchLinux\ImportFluxBB\Importer\TopicSubscriptions;
use ArchLinux\ImportFluxBB\Importer\Users;
use Flarum\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ImportFromFluxBB extends AbstractCommand
{
    private Users $users;
    private Avatars $avatars;
    private Categories $categories;
    private Forums $forums;
    private Topics $topics;
    private Posts $posts;
    private TopicSubscriptions $topicSubscriptions;
    private ForumSubscriptions $forumSubscriptions;
    private Groups $groups;
    private Bans $bans;
    private Reports $reports;
    private PostMentionsUser $postMentionsUser;
    private InitialCleanup $initialCleanup;

    public function __construct(
        Users $users,
        Categories $categories,
        Forums $forums,
        Avatars $avatars,
        Topics $topics,
        Posts $posts,
        TopicSubscriptions $topicSubscriptions,
        ForumSubscriptions $forumSubscriptions,
        Groups $groups,
        Bans $bans,
        Reports $reports,
        PostMentionsUser $postMentionsUser,
        InitialCleanup $initialCleanup
    ) {
        $this->users = $users;
        $this->categories = $categories;
        $this->forums = $forums;
        $this->avatars = $avatars;
        $this->topics = $topics;
        $this->posts = $posts;
        $this->topicSubscriptions = $topicSubscriptions;
        $this->forumSubscriptions = $forumSubscriptions;
        $this->groups = $groups;
        $this->bans = $bans;
        $this->reports = $reports;
        $this->postMentionsUser = $postMentionsUser;
        $this->initialCleanup = $initialCleanup;
        parent::__construct();
    }

    protected function configure()
    {
        // For inspiration see:
        // https://github.com/sineld/import-from-fluxbb-to-flarum
        // https://github.com/mondediefr/fluxbb_to_flarum
        // also https://github.com/pierres/ll/blob/fluxbb/FluxImport.php
        $this
            ->setName('app:import-from-fluxbb')
            ->setDescription('Import from FluxBB database')
            ->addArgument('fluxbb-database', InputArgument::OPTIONAL, '', 'fluxbb')
            ->addArgument('avatars-dir', InputArgument::OPTIONAL, '', '/fluxbb-avatars')
            ->addOption('restart-from', null, InputOption::VALUE_OPTIONAL, 'Restart import from step (user|avatar|topics|posts|subscriptions|groups|bans|reports|mentions|validation)', '')
            ->addOption('resolved', null, InputOption::VALUE_REQUIRED, 'Pattern for resolved tag)', '(gel(ö|oe)(s|ss|ß)t|(re)?solved|erledigt|done|geschlossen)');
    }

    protected function fire()
    {
        ini_set('memory_limit', '16G');
        define('CAT_INCREMENT', 500);
        $stepArray = [
            'user' => 1,
            'avatar' => 2,
            'topics' => 4,
            'posts' => 5,
            'subscriptions' => 6,
            'groups' => 7,
            'bans' => 8,
            'reports' => 9,
            'mentions' => 10,
            'validation' => 11
        ];

        $step = $this->input->getOption('restart-from');
        if (empty($step)) {
            $step = 0;
        } else {
            if (isset($stepArray[$step])) {
                $step = $stepArray[$step];
            } else {
                $this->output->write("<error>${step} is not a step in list !</error>");
                return 1;
            }

        }

        $this->initialCleanup->execute($this->output, $step);

        if ($step<=1) {
            $this->users->execute($this->output, $this->input->getArgument('fluxbb-database'));
        }
        if ($step<=2) {
            $this->avatars->execute(
                $this->output,
                $this->input->getArgument('fluxbb-database'),
                $this->input->getArgument('avatars-dir')
            );
        }
        if ($step<=4) {
            $this->categories->execute($this->output, $this->input->getArgument('fluxbb-database'));
            $this->forums->execute($this->output, $this->input->getArgument('fluxbb-database'));
            $this->topics->execute(
                $this->output,
                $this->input->getArgument('fluxbb-database'),
                $this->input->getOption('resolved')
            );
        }
        if ($step<=5) {
            $this->posts->execute($this->output, $this->input->getArgument('fluxbb-database'));
        }
        if ($step<=6) {
            $this->topicSubscriptions->execute($this->output, $this->input->getArgument('fluxbb-database'));
            $this->forumSubscriptions->execute($this->output, $this->input->getArgument('fluxbb-database'));
        }
        if ($step<=7) {
            $this->groups->execute($this->output, $this->input->getArgument('fluxbb-database'));
        }
        if ($step<=8) {
            $this->bans->execute($this->output, $this->input->getArgument('fluxbb-database'));
        }
        if ($step<=9) {
            $this->reports->execute($this->output, $this->input->getArgument('fluxbb-database'));
        }
        if ($step<=10) {
            $this->postMentionsUser->execute($this->output);
        }
        if ($step<=11) {
            $this->validation->execute($this->output);
        }
    }
}
