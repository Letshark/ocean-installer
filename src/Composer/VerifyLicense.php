<?php

namespace Ocean\Installer\Composer;

use Composer\Script\Event;
use GuzzleHttp\Client;

class VerifyLicense
{
    public static function check(Event $event)
    {
        $io = $event->getIO();

        $io->write("<info>🔑 Verifying Ocean token license...</info>");

        // 1. Minta token
        $license = getenv('OCEAN_LICENSE');
        if (!$license) {
            $license = $io->askAndHideAnswer('Input token license: ');
        }

        if (!$license) {
            throw new \RuntimeException("❌ Token required.");
        }

        // 2. Validasi ke API Letshark
        $client = new Client([
            'base_uri' => 'https://api.letshark.com',
            'timeout' => 10,
        ]);

        try {
            $response = $client->post('/ocean/license/verify', [
                'token' => $license
            ]);
            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException("❌ Failed to verify license. Error code " . $response->getStatusCode());
            }
            $data = json_decode($response->getBody(), true);

            if (empty($data['token'])) {
                throw new \RuntimeException("❌ Invalid token.");
            }

            $io->write("<info>✅ Token validate. Generate and saving auth token...</info>");

            $authFile = getenv("HOME") . "/.composer/auth.json";
            $authData = [];

            if (file_exists($authFile)) {
                $authData = json_decode(file_get_contents($authFile), true);
            }

            $authData['license'] = $license ?? $license;
            $authData['auth'] = $data['token'] ?? '';

            file_put_contents($authFile, json_encode($authData, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            throw new \RuntimeException("❌ Failed to verify license: " . $e->getMessage());
        }
    }
}
