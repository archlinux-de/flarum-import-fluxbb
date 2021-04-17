<?php

namespace ArchLinux\ImportFluxBB\Importer;

use Flarum\Foundation\Paths;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class Avatars
{
    private ConnectionInterface $database;
    private string $fluxBBDatabase;
    private string $avatarsDir;
    private ContainerInterface $container;

    public function __construct(ConnectionInterface $database, ContainerInterface $container)
    {
        $this->database = $database;
        $this->container = $container;
    }

    public function execute(OutputInterface $output, string $fluxBBDatabase, string $avatarsDir)
    {
        $this->fluxBBDatabase = $fluxBBDatabase;
        $this->avatarsDir = $avatarsDir;
        $output->writeln('Importing avatars...');

        $users = $this->database
            ->table($this->fluxBBDatabase . '.users')
            ->select(['id'])
            ->where('username', '!=', 'Guest')
            ->orderBy('id')
            ->get()
            ->all();

        $progressBar = new ProgressBar($output, count($users));

        foreach ($users as $user) {
            $this->database
                ->table('users')
                ->where('id', '=', $user->id)
                ->update(['avatar_url' => $this->createAvatarUrl($user->id)]);
            $progressBar->advance();
        }
        $progressBar->finish();

        $output->writeln('');
    }

    /**
     * @param int $userId
     * @return string|null
     */
    private function createAvatarUrl(int $userId): ?string
    {
        $avatarFile = glob($this->avatarsDir . '/' . $userId . '.*');
        if (!$avatarFile) {
            return null;
        }
        $avatarFile = $avatarFile[0];

        $newFileName = Str::random() . '.png';
        $newPath = $this->container[Paths::class]->public . '/assets/avatars/' . $newFileName;
        if (file_exists($newPath)) {
            throw new \RuntimeException('Avatar already exists: ' . $newFileName);
        }

        Image::configure(['driver' => 'imagick']);
        $image = Image::make($avatarFile);
        if (!Str::endsWith($avatarFile, '.png')
            || $image->getWidth() !== $image->getHeight()
            || $image->getWidth() > 100) {
            $newSize = max($image->getWidth(), $image->getHeight());
            if ($newSize > 100) {
                $newSize = 100;
            }
            $encodedImage = $image->orientate()->fit($newSize, $newSize)->encode('png');
            file_put_contents($newPath, $encodedImage);
        } else {
            copy($avatarFile, $newPath);
        }
        system('optipng -o 5 -strip all -snip -quiet ' . $newPath);

        return $newFileName;
    }
}
