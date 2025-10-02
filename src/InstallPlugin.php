<?php

namespace Ocean\Installer;

use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\Event;

class InstallPlugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(\Composer\Composer $composer, \Composer\IO\IOInterface $io)
    {
        $io->write("<info>Ocean Installer activated!</info>");
    }

    public static function getSubscribedEvents()
    {
        return [
            'post-install-cmd' => 'onPostInstall',
            'post-update-cmd' => 'onPostInstall',
        ];
    }

    public function onPostInstall(Event $event)
    {
        $io = $event->getIO();
        $io->write("<comment>Verifying Ocean license...</comment>");

        // contoh cek lisensi
        // kalau gagal -> throw exception
    }
}
