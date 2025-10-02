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
            ->setDescription('Create Ocean project')
            ->addArgument('name', InputArgument::REQUIRED, 'Project name (folder)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        $helper = $this->getHelper('question');
        $question = new Question('Input token license: ');
        $license = $helper->ask($input, $output, $question);

        $output->writeln("<info>🔑 Verifying license token...</info>");

        $client = new Client([
            'base_uri' => 'https://api.letshark.com',
            'timeout'  => 10,
        ]);

        try {
            $response = $client->post('/ocean/license/verify', [
                'token' => $license,
            ]);

            $data = json_decode($response->getBody(), true);

            if (empty($data['token']) || !$data['token']) {
                $output->writeln("<error>❌ Invalid license.</error>");
                return Command::FAILURE;
            }

            $output->writeln("<info>✅ License validate, preparing new project {$name}...</info>");

            $zipPath = sys_get_temp_dir() . "/ocean-{$name}.zip";
            file_put_contents($zipPath, fopen($data['content'], 'r'));

            $zip = new ZipArchive;
            if ($zip->open($zipPath) === true) {
                $zip->extractTo($name);
                $zip->close();
                unlink($zipPath);

                $composerAuthPath = getenv("HOME") . "/.composer/auth.json";
                $composerAuth = [
                    "license" => $license,
                    "token" => $data['token'] ?? '',
                ];
                file_put_contents($composerAuthPath, json_encode($composerAuth, JSON_PRETTY_PRINT));
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
}
