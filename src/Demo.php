<?php

declare(strict_types=1);

namespace App;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainerFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Symfony\Component\Yaml\Yaml;

final class Demo
{
    public function __construct(
        private Arboretum $arboretum,
        private CommandHandler $commandHandler
    ) {
    }

    public static function boot(): void
    {
        $contentRepositoryRegistry = new StandaloneContentRepositoryRegistry(
            dbalConnection: self::getConnection(),
            dimensionConfiguration: [],
            nodeTypeConfiguration: Yaml::parseFile(__DIR__ . '/../Settings.NodeTypes.yaml')
        );

        $crMaintainer = $contentRepositoryRegistry->buildService(ContentRepositoryId::fromString('default'), new ContentRepositoryMaintainerFactory());
        $crMaintainer->setUp();

        $contentRepository = $contentRepositoryRegistry->get(ContentRepositoryId::fromString('default'));

        try {
            $liveWorkspace = $contentRepository->getContentGraph(WorkspaceName::forLive());
        } catch (WorkspaceDoesNotExist) {
            $contentRepository->handle(CreateRootWorkspace::create(WorkspaceName::forLive(), ContentStreamId::fromString('live-cs-id')));
            $liveWorkspace = $contentRepository->getContentGraph(WorkspaceName::forLive());
        }

        $instance = new self(
            new Arboretum(
                $liveWorkspace
            ),
            new CommandHandler(
                $contentRepository
            )
        );

        $instance->run(
            verbose: true
        );
    }

    private static function getConnection(): Connection
    {
        $persistence = Yaml::parseFile(__DIR__ . '/../Settings.Persistence.yaml');

        $connectionParams = [
            'dbname' => $persistence['persistence']['dbname'],
            'user' => $persistence['persistence']['user'],
            'password' => $persistence['persistence']['password'],
            'host' => $persistence['persistence']['host'],
            'driver' => $persistence['persistence']['driver'],
            'port' => $persistence['persistence']['port'] ?? 3306
        ];

        $connection = DriverManager::getConnection($connectionParams);
        $connection->connect();
        return $connection;
    }

    public function run(bool $verbose): void
    {
        while (true) {
            $shortCommandName = trim(readline("\n> Command: ") ?: '');
            if ($shortCommandName === 'quit' || $shortCommandName === 'q') {
                return;
            }
            if ($shortCommandName === 'print' || $shortCommandName === 'p') {
                echo "\n";
                echo $this->arboretum->toAscii(DimensionSpacePoint::createWithoutDimensions(), VisibilityConstraints::createEmpty());
                echo "\n";
                continue;
            }
            readline_add_history($shortCommandName);
            try {
                $options = $this->commandHandler->getCommandOptions($shortCommandName);
            } catch (\Exception $e) {
                echo sprintf('Invalid command: %s', $e->getMessage());
                echo "\nType to (q)uit\n";
                continue;
            }

            $inputOptions = [];
            foreach ($options as $option) {
                $optionValue = readline("> Argument $option: ") ?: null;
                $inputOptions[$option] = $optionValue;
            }


            try {
                $this->commandHandler->handleCommand($shortCommandName, $inputOptions);
            } catch (\Exception $e) {
                echo sprintf('Exception (%s, %s): %s' . PHP_EOL, (new \ReflectionClass($e::class))->getShortName(), $e->getCode(), $e->getMessage());
                if ($verbose) {
                    echo $e->getTraceAsString();
                    echo PHP_EOL;
                }
                if ($prev = $e->getPrevious()) {
                    echo sprintf('Previous (%s, %s): %s' . PHP_EOL, (new \ReflectionClass($prev::class))->getShortName(), $prev->getCode(), $prev->getMessage());
                    if ($verbose) {
                        echo $prev->getTraceAsString();
                        echo PHP_EOL;
                    }
                }
                echo "\nType to (q)uit\n\n";

                continue;
            }

            echo "\n";
            echo $this->arboretum->toAscii(DimensionSpacePoint::createWithoutDimensions(), VisibilityConstraints::createEmpty());
            echo "\n";
        }
    }
}
