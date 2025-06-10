<?php

declare(strict_types=1);

namespace App;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\AddDimensionShineThrough;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\MoveDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\EnableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
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

final readonly class CommandHandler
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
        DisableNodeAggregate::class,
        DiscardIndividualNodesFromWorkspace::class,
        DiscardWorkspace::class,
        EnableNodeAggregate::class,
        MoveDimensionSpacePoint::class,
        MoveNodeAggregate::class,
        PublishIndividualNodesFromWorkspace::class,
        PublishWorkspace::class,
        RebasableToOtherWorkspaceInterface::class,
        RebaseWorkspace::class,
        RemoveNodeAggregate::class,
        SetNodeProperties::class,
        SetNodeReferences::class,
        TagSubtree::class,
        UntagSubtree::class,
        UpdateRootNodeAggregateDimensions::class,
    ];

    public function __construct(
        private ContentRepository $contentRepository
    ) {
    }

    /**
     * @param array<mixed> $commandArguments
     */
    public function handleCommand(string $shortCommandName, array $commandArguments): void
    {
        $commandClassName = self::resolveShortCommandName($shortCommandName);

        $commandArguments = $this->addDefaultCommandArgumentValues($commandClassName, $commandArguments);
        $command = $commandClassName::fromArray($commandArguments);
        // if ($command instanceof CreateRootNodeAggregateWithNode) {
        //     $this->currentRootNodeAggregateId = $command->nodeAggregateId;
        // }
        $this->contentRepository->handle($command);
    }

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
     * @param class-string<CommandInterface> $commandClassName
     * @param array<mixed> $commandArguments
     * @return array<mixed>
     */
    private function addDefaultCommandArgumentValues(string $commandClassName, array $commandArguments): array
    {
        // $commandArguments['workspaceName'] = $commandArguments['workspaceName'] ?? $this->currentWorkspaceName?->value;
        // $commandArguments['coveredDimensionSpacePoint'] = $commandArguments['coveredDimensionSpacePoint'] ?? $this->currentDimensionSpacePoint?->coordinates;
        if (is_string($commandArguments['coveredDimensionSpacePoint'] ?? null)) {
            $commandArguments['coveredDimensionSpacePoint'] = \json_decode($commandArguments['coveredDimensionSpacePoint'], true, 512, JSON_THROW_ON_ERROR);
        }
        // $commandArguments['dimensionSpacePoint'] = $commandArguments['dimensionSpacePoint'] ?? $this->currentDimensionSpacePoint?->coordinates;
        // if (is_string($commandArguments['nodeAggregateId'] ?? null) && str_starts_with($commandArguments['nodeAggregateId'], '$')) {
        //     $commandArguments['nodeAggregateId'] = $this->rememberedNodeAggregateIds[substr($commandArguments['nodeAggregateId'], 1)]?->value;
        // } elseif (!isset($commandArguments['nodeAggregateId'])) {
        //     $commandArguments['nodeAggregateId'] = $this->getCurrentNodeAggregateId()?->value;
        // }
        if ($commandClassName === CreateNodeAggregateWithNode::class) {
            if (is_string($commandArguments['initialPropertyValues'] ?? null)) {
                $commandArguments['initialPropertyValues'] = self::deserializeProperties(json_decode($commandArguments['initialPropertyValues'], true, 512, JSON_THROW_ON_ERROR))->values;
            } elseif (is_array($commandArguments['initialPropertyValues'] ?? null)) {
                // $commandArguments['initialPropertyValues'] = self::deserializeProperties($commandArguments['initialPropertyValues'])->values;
            }
            if (isset($commandArguments['succeedingSiblingNodeAggregateId']) && $commandArguments['succeedingSiblingNodeAggregateId'] === '') {
                unset($commandArguments['succeedingSiblingNodeAggregateId']);
            }
            // if (is_string($commandArguments['parentNodeAggregateId'] ?? null) && str_starts_with($commandArguments['parentNodeAggregateId'], '$')) {
            //     $commandArguments['parentNodeAggregateId'] = $this->rememberedNodeAggregateIds[substr($commandArguments['parentNodeAggregateId'], 1)]?->value;
            // }
            if (empty($commandArguments['nodeName'])) {
                unset($commandArguments['nodeName']);
            }
        }
        // if ($commandClassName === SetNodeProperties::class) {
        //     if (is_string($commandArguments['propertyValues'] ?? null)) {
        //         $commandArguments['propertyValues'] = $this->deserializeProperties(json_decode($commandArguments['propertyValues'], true, 512, JSON_THROW_ON_ERROR))->values;
        //     } elseif (is_array($commandArguments['propertyValues'] ?? null)) {
        //         $commandArguments['propertyValues'] = $this->deserializeProperties($commandArguments['propertyValues'])->values;
        //     }
        // }
        if ($commandClassName === CreateNodeAggregateWithNode::class || $commandClassName === SetNodeProperties::class) {
            if (is_string($commandArguments['originDimensionSpacePoint'] ?? null) && !empty($commandArguments['originDimensionSpacePoint'])) {

                $commandArguments['originDimensionSpacePoint'] = OriginDimensionSpacePoint::fromJsonString($commandArguments['originDimensionSpacePoint'])->coordinates;
            } elseif (!isset($commandArguments['originDimensionSpacePoint'])) {
                // $commandArguments['originDimensionSpacePoint'] = $this->currentDimensionSpacePoint?->coordinates;
            }
        }
        // if ($commandClassName === CreateNodeAggregateWithNode::class || $commandClassName === SetNodeReferences::class) {
        //     if (is_string($commandArguments['references'] ?? null)) {
        //         $commandArguments['references'] = iterator_to_array($this->mapRawNodeReferencesToNodeReferencesToWrite(json_decode($commandArguments['references'], true, 512, JSON_THROW_ON_ERROR)));
        //     } elseif (is_array($commandArguments['references'] ?? null)) {
        //         $commandArguments['references'] = iterator_to_array($this->mapRawNodeReferencesToNodeReferencesToWrite($commandArguments['references']));
        //     }
        // }
        // if ($commandClassName === SetNodeReferences::class) {
        //     if (is_string($commandArguments['sourceOriginDimensionSpacePoint'] ?? null) && !empty($commandArguments['sourceOriginDimensionSpacePoint'])) {
        //         $commandArguments['sourceOriginDimensionSpacePoint'] = OriginDimensionSpacePoint::fromJsonString($commandArguments['sourceOriginDimensionSpacePoint'])->coordinates;
        //     } elseif (!isset($commandArguments['sourceOriginDimensionSpacePoint'])) {
        //         $commandArguments['sourceOriginDimensionSpacePoint'] = $this->currentDimensionSpacePoint?->coordinates;
        //     }
        //     if (is_string($commandArguments['sourceNodeAggregateId'] ?? null) && str_starts_with($commandArguments['sourceNodeAggregateId'], '$')) {
        //         $commandArguments['sourceNodeAggregateId'] = $this->rememberedNodeAggregateIds[substr($commandArguments['sourceNodeAggregateId'], 1)]?->value;
        //     } elseif (!isset($commandArguments['sourceNodeAggregateId'])) {
        //         $commandArguments['sourceNodeAggregateId'] = $this->currentNodeAggregate?->nodeAggregateId->value;
        //     }
        // }
        if ($commandClassName === CreateNodeAggregateWithNode::class || $commandClassName === ChangeNodeAggregateType::class || $commandClassName === CreateRootNodeAggregateWithNode::class) {
            if (is_string($commandArguments['tetheredDescendantNodeAggregateIds'] ?? null)) {
                if ($commandArguments['tetheredDescendantNodeAggregateIds'] === '') {
                    unset($commandArguments['tetheredDescendantNodeAggregateIds']);
                } else {
                    $commandArguments['tetheredDescendantNodeAggregateIds'] = json_decode($commandArguments['tetheredDescendantNodeAggregateIds'], true, 512, JSON_THROW_ON_ERROR);
                }
            }
        }
        return $commandArguments;
    }

    /**
     * @param array<mixed> $properties
     */
    private static function deserializeProperties(array $properties): PropertyValuesToWrite
    {
        return PropertyValuesToWrite::fromArray(
            array_map(
                static fn (mixed $value) => is_array($value) && isset($value['__type']) ? new $value['__type']($value['value']) : $value,
                $properties
            )
        );
    }

    /**
     * @return class-string<CommandInterface>
     */
    private static function resolveShortCommandName(string $shortCommandName): string
    {
        foreach (self::AVAILABLE_COMMANDS as $commandClassName) {
            if (substr(strrchr($commandClassName, '\\') ?: '', 1) === $shortCommandName) {
                return $commandClassName;
            }
        }
        throw new \RuntimeException('The short command name "' . $shortCommandName . '" is currently not supported.');
    }
}
