<?php

namespace Ocean\Installer\Commands;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use ZipArchive;

class CreateCommand extends Command
{
    protected static $defaultName = 'create';

    protected function configure()
    {
        $this
            ->setName('create')
            ->setDescription('Create Ocean project')
            ->addArgument('name', InputArgument::OPTIONAL, 'Project name (folder)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        $helper = $this->getHelper('question');
        if (empty($name)) {
            $question = new Question('Input project name: ');
            $name = $helper->ask($input, $output, $question);
        }

        try {
            $data = $this->verify($input, $output);

            if ($data === false) {
                return Command::FAILURE;
            }

            $zipPath = sys_get_temp_dir() . "/ocean-{$name}.zip";
            file_put_contents($zipPath, fopen($data['content'], 'r'));

            $zip = new ZipArchive;
            if ($zip->open($zipPath) === true) {
                $zip->extractTo($name);
                $zip->close();
                unlink($zipPath);
                $output->writeln("<info>📦 {$name} project created successfully.</info>");
            } else {
                $output->writeln("<error>Failed to create new project, Error: Unable download/install Ocean</error>");
                return Command::FAILURE;
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<error>Failed to create new project, Error: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }

    protected function verify(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $output->writeln("<info>🔑 Verifying Ocean license token...</info>");
        $license = getenv('OCEAN_LICENSE');

        do {
            $question = new Question('Input license token: ');
            $license = $helper->ask($input, $output, $question);
        } while (!$license);

        file_put_contents(getcwd() . '/.env', "OCEAN_LICENSE={$license}\n", FILE_APPEND);
        putenv("OCEAN_LICENSE={$license}");

        // 2. Validasi ke API Letshark
        $client = new Client([
            'base_uri' => 'https://api.letshark.com',
            'timeout' => 10,
        ]);

        $response = $client->post('/ocean/license/verify', [
            'token' => $license
        ]);
        if ($response->getStatusCode() !== 200) {
            $output->writeln("<error>🔑 ❌ Failed to verify license. Error code " . $response->getStatusCode() . "</error>");
            return false;
        }
        $data = json_decode($response->getBody(), true);

        if (empty($data['token'])) {
            $output->writeln("<error>🔑 ❌ Invalid license token.</error>");
            return false;
        }

        $output->writeln("<info>✅ Token validate. Generate and saving auth token...</info>");

        $authFile = getenv("HOME") . "/.composer/auth.json";
        $authData = [];

        if (file_exists($authFile)) {
            $authData = json_decode(file_get_contents($authFile), true);
        }

        $authData['license'] = $license ?? $license;
        $authData['auth'] = $data['token'] ?? '';

        file_put_contents($authFile, json_encode($authData, JSON_PRETTY_PRINT));
        return $data;
    }
}
