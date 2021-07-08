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
use ArchLinux\ImportFluxBB\Importer\Validation;
use Flarum\Console\AbstractCommand;
use Flarum\Extension\ExtensionManager;
use Symfony\Component\Console\Input\InputArgument;

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
    private Validation $validation;
    private ExtensionManager $extensionManager;

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
        InitialCleanup $initialCleanup,
        Validation $validation,
        ExtensionManager $extensionManager
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
        $this->validation = $validation;
        $this->extensionManager = $extensionManager;
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
            ->setDescription('Import from FluxBB')
            ->addArgument('fluxbb-database', InputArgument::OPTIONAL, '', 'fluxbb')
            ->addArgument('avatars-dir', InputArgument::OPTIONAL, '', '/fluxbb-avatars');
    }

    protected function fire()
    {
        $requiredExtensions = [
            'flarum-bbcode',
            'flarum-emoji',
            'flarum-mentions',
            'flarum-nicknames',
            'flarum-sticky',
            'flarum-subscriptions',
            'flarum-tags',
            'flarum-suspend',
            'flarum-lock',
            'migratetoflarum-old-passwords'
        ];
        foreach ($requiredExtensions as $requiredExtension) {
            if (!$this->extensionManager->isEnabled($requiredExtension)) {
                $this->error($requiredExtension . ' extension needs to be enabled');
                return;
            }
        }

        ini_set('memory_limit', '16G');
        define('CAT_INCREMENT', 500);

        $this->initialCleanup->execute($this->output);
        $this->users->execute($this->output, $this->input->getArgument('fluxbb-database'));
        $this->avatars->execute(
            $this->output,
            $this->input->getArgument('fluxbb-database'),
            $this->input->getArgument('avatars-dir')
        );
        $this->categories->execute($this->output, $this->input->getArgument('fluxbb-database'));
        $this->forums->execute($this->output, $this->input->getArgument('fluxbb-database'));
        $this->topics->execute($this->output, $this->input->getArgument('fluxbb-database'));
        $this->posts->execute($this->output, $this->input->getArgument('fluxbb-database'));
        $this->topicSubscriptions->execute($this->output, $this->input->getArgument('fluxbb-database'));
        $this->forumSubscriptions->execute($this->output, $this->input->getArgument('fluxbb-database'));
        $this->groups->execute($this->output, $this->input->getArgument('fluxbb-database'));
        $this->bans->execute($this->output, $this->input->getArgument('fluxbb-database'));
        $this->reports->execute($this->output, $this->input->getArgument('fluxbb-database'));
        $this->postMentionsUser->execute($this->output);

        $this->validation->execute($this->output);
    }
}
