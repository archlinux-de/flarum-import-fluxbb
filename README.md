## FluxBB Importer

This [Flarum](https://flarum.org/) extension is specific to [forum.archlinux.de](https://forum.archlinux.de/). You might
find its code useful to implement your own solution.

### Installation

```sh
composer require archlinux-de/flarum-import-fluxbb
```

Don't forget to enable *FluxBB Importer*, *Old Passwords* and *Nicknames* extensions from admin interface.

### Usage

```sh
./flarum app:import-from-fluxbb  [<fluxbb-database> [<avatars-dir>]]
```
