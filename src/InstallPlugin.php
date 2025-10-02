<?php

namespace Ocean\Installer;

use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\Event;

class InstallPlugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io) {}
    public function deactivate(Composer $composer, IOInterface $io) {}
    public function uninstall(Composer $composer, IOInterface $io) {}

    public static function getSubscribedEvents()
    {
        return [
            'post-install-cmd' => 'setup',
            'post-update-cmd' => 'setup',
        ];
    }

    public function setup(Event $event)
    {
        $io = $event->getIO();
        $io->write("<comment>Verifying Ocean license...</comment>");

        // contoh cek lisensi
        // kalau gagal -> throw exception
    }
}
