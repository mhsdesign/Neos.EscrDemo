<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'disintegrate')]
class DisintegrateCommand extends Command
{
    public function __construct(
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $schemaManager = $this->connection->createSchemaManager();
        $dropSchemaSql = $schemaManager->introspectSchema()->toDropSql($this->connection->getDatabasePlatform());

        foreach ($dropSchemaSql as $sql) {
            $this->connection->executeStatement($sql);
        }

        $output->writeln(sprintf('Dropped %s tables', count($dropSchemaSql)));

        // todo destroy all sessions?
        return Command::SUCCESS;
    }
}
