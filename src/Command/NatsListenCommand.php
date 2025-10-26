<?php

namespace App\Command;

// Use statements for Symfony Console
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle; // For nice I/O

// Use statements for NATS
use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Basis\Nats\Message\Payload;
use Basis\Nats\Stream\RetentionPolicy;
use Basis\Nats\Stream\StorageBackend; // Import storage type

class NatsListenCommand extends Command
{

    protected function configure(): void
    {
        $this->setName('nats:listen')
            ->setDescription('Runs a persistent NATS topic listener.');
    }

    // This is where your listener.php logic goes
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // $io provides a nicer way to write to the console
        $io = new SymfonyStyle($input, $output);

        // --- 1. Configure the NATS connection ---
        $config = new Configuration(
            host: $_ENV['NATS_HOST'] ?? 'nats',
            port: (int)($_ENV['NATS_PORT'] ?? 4222),
            user: $_ENV['NATS_USER'] ?? null,
            pass: $_ENV['NATS_PASS'] ?? null
        );

        $client = new Client($config);

        try {
            $io->info("Connecting to NATS at " . $config->host . ":" . $config->port . "...");

            // Test connection with ping
            if ($client->ping()) {
                $io->success("Successfully connected.");
            } else {
                $io->error("Failed to connect to NATS server");
                return Command::FAILURE;
            }


            $api = $client->getApi();
            $stream = $api->getStream('NODE_DATA');


            if (!$stream->exists()) {
                $io->warning("Stream 'NODE_DATA' not found. Creating it...");
                $stream->getConfiguration()
                    ->setRetentionPolicy(RetentionPolicy::WORK_QUEUE)
                    ->setStorageBackend(StorageBackend::MEMORY)
                    ->setSubjects(['nodes.data.>']); // Capture the subject from env
                $stream->create();
                $io->info("Stream created.");
            }

            $consumer = $stream->getConsumer('Center_Data_Consumer');
            if (!$consumer->exists()) {
                $io->warning("Consumer 'Center_Data_Consumer' not found. Creating it...");
                $consumer->getConfiguration()
                    ->setSubjectFilter('nodes.data.>'); // Only listen to the subject from env
                $consumer->create();
                $io->info("Consumer created.");
            }

            $consumer->handle(function ($payload) use ($io) {
                $messageData = $payload;
                $io->info("Received: " . $messageData);
            });
            $io->info("Consumer Info: " . json_encode($consumer->info()));


            // --- 3. Keep the listener alive ---
            while (true) {
                $client->process(1);
            }
        } catch (\Exception $e) {
            $io->error("Error: " . $e->getMessage());
            return Command::FAILURE; // Return error code
        }
    }
}
