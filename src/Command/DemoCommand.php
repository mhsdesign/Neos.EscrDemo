<?php

declare(strict_types=1);

namespace App\Command;

use App\Arboretum;
use App\CommandHandler;
use App\CommandHelper;
use App\ContentRepositoryFactoryBuilder;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainerFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'demo:start')]
class DemoCommand extends Command
{
    public function __construct(
        private ContentRepositoryFactoryBuilder $contentRepositoryFactoryBuilder,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $contentRepositoryFactory = $this->contentRepositoryFactoryBuilder->createForId(ContentRepositoryId::fromString('default'));
        $crMaintainer = $contentRepositoryFactory->buildService(new ContentRepositoryMaintainerFactory());
        $crMaintainer->setUp();
        $contentRepository = $contentRepositoryFactory->getOrBuild();

        try {
            $liveWorkspace = $contentRepository->getContentGraph(WorkspaceName::forLive());
        } catch (WorkspaceDoesNotExist) {
            $contentRepository->handle(CreateRootWorkspace::create(WorkspaceName::forLive(), ContentStreamId::fromString('live-cs-id')));
            $liveWorkspace = $contentRepository->getContentGraph(WorkspaceName::forLive());
        }

        $arboretum = new Arboretum(
            $liveWorkspace
        );

        $commandHandler = new CommandHandler(
            $contentRepository
        );

        $commandHelper = new CommandHelper();

        $verbose = $input->getOption('verbose');

        while (true) {
            $shortCommandName = trim(readline("\n> Command: ") ?: '');
            if ($shortCommandName === 'quit' || $shortCommandName === 'q') {
                return Command::SUCCESS;
            }
            if ($shortCommandName === 'print' || $shortCommandName === 'p') {
                echo "\n";
                echo $arboretum->toAscii(DimensionSpacePoint::createWithoutDimensions(), VisibilityConstraints::createEmpty());
                echo "\n";
                continue;
            }
            readline_add_history($shortCommandName);
            try {
                $options = $commandHelper->getCommandOptions($shortCommandName);
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
                $commandHandler->handleCommand($shortCommandName, $inputOptions);
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
            echo $arboretum->toAscii(DimensionSpacePoint::createWithoutDimensions(), VisibilityConstraints::createEmpty());
            echo "\n";
        }
    }
}
