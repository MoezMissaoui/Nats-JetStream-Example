<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Basis\Nats\Stream\RetentionPolicy;
use Basis\Nats\Stream\StorageBackend;

class NatsPublishCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('nats:publish')
            ->setDescription('Publish a message to a JetStream stream (auto-creates stream if missing).')
            ->addArgument('message', InputArgument::OPTIONAL, 'Message payload to publish');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // NATS connection
        $config = new Configuration(
            host: $_ENV['NATS_HOST'] ?? 'nats',
            port: (int)($_ENV['NATS_PORT'] ?? 4222),
            user: $_ENV['NATS_USER'] ?? null,
            pass: $_ENV['NATS_PASS'] ?? null
        );
        $client = new Client($config);

        try {
            $io->info("Connecting to NATS at {$config->host}:{$config->port}...");
            if (!$client->ping()) {
                $io->error('Failed to connect to NATS server');
                return Command::FAILURE;
            }
            $io->success('Connected');

            $api = $client->getApi();

            // Resolve stream and subjects
            $streamName = 'CENTER_DATA';
            $subjectsWildcard = 'center.data.*.*';

            $stream = $api->getStream($streamName);
            if (!$stream->exists()) {
                $io->warning("Stream '{$streamName}' not found. Creating...");
                $stream->getConfiguration()
                    ->setRetentionPolicy(RetentionPolicy::WORK_QUEUE)
                    ->setStorageBackend(StorageBackend::MEMORY)
                    ->setSubjects([$subjectsWildcard]);
                $stream->create();
                $io->success('Stream created');
            } else {
                $io->info("Using existing stream '{$streamName}'");
            }

            // Subject and payload
            $subject = 'center.data.test.123456';
            $message = $input->getArgument('message') ?? 'hello from center';

            // Publish
            $io->info("Publishing to '{$subject}' on stream '{$streamName}'...");
            $stream->put($subject, $message);
            $io->success('Message published');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}