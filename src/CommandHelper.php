<?php

declare(strict_types=1);

namespace App;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\AddDimensionShineThrough;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\MoveDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeMove\Command\MoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Command\ChangeNodeAggregateName;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\UpdateRootNodeAggregateDimensions;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\TagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\UntagSubtree;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\ChangeBaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\DeleteWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;

final readonly class CommandHelper
{
    /** @var list<class-string<CommandInterface>> */
    private const AVAILABLE_COMMANDS = [
        AddDimensionShineThrough::class,
        ChangeBaseWorkspace::class,
        ChangeNodeAggregateName::class,
        ChangeNodeAggregateType::class,
        CreateNodeAggregateWithNode::class,
        CreateNodeVariant::class,
        CreateRootNodeAggregateWithNode::class,
        CreateRootWorkspace::class,
        CreateWorkspace::class,
        DeleteWorkspace::class,
        DiscardIndividualNodesFromWorkspace::class,
        DiscardWorkspace::class,
        MoveDimensionSpacePoint::class,
        MoveNodeAggregate::class,
        PublishIndividualNodesFromWorkspace::class,
        PublishWorkspace::class,
        RebaseWorkspace::class,
        RemoveNodeAggregate::class,
        SetNodeProperties::class,
        SetNodeReferences::class,
        TagSubtree::class,
        UntagSubtree::class,
        UpdateRootNodeAggregateDimensions::class,
    ];

    /**
     * @return list<string>
     */
    public function getAvailableCommands(): array
    {
        $commands = [];
        foreach (self::AVAILABLE_COMMANDS as $commandClassName) {
            $commands[] = substr(strrchr($commandClassName, '\\') ?: '', 1);
        }
        return $commands;
    }

    /**
     * @return array<mixed>
     */
    public function getCommandOptions(string $shortCommandName): array
    {
        $commandClassName = self::resolveShortCommandName($shortCommandName);
        return array_map(
            fn (\ReflectionParameter $parameter) => $parameter->name,
            (new \ReflectionClass($commandClassName))->getConstructor()?->getParameters() ?? []
        );
    }

    /**
     * @return class-string<CommandInterface>
     */
    public static function resolveShortCommandName(string $shortCommandName): string
    {
        foreach (self::AVAILABLE_COMMANDS as $commandClassName) {
            if (substr(strrchr($commandClassName, '\\') ?: '', 1) === $shortCommandName) {
                return $commandClassName;
            }
        }
        throw new \RuntimeException('The short command name "' . $shortCommandName . '" is currently not supported.');
    }
}
