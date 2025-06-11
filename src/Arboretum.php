<?php

declare(strict_types=1);

namespace App;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;

final readonly class Arboretum
{
    public function __construct(
        public ContentGraphInterface $contentGraph
    ) {
    }

    public function toAscii(DimensionSpacePoint $dimensionSpacePoint, VisibilityConstraints $visibilityConstraints): string
    {
        $subgraph = $this->contentGraph->getSubgraph($dimensionSpacePoint, $visibilityConstraints);
        $lines = [];
        foreach ($this->contentGraph->findRootNodeAggregates(FindRootNodeAggregatesFilter::create()) as $rootNodeAggregate) {
            $stack = [$rootNodeAggregate->getNodeByOccupiedDimensionSpacePoint(OriginDimensionSpacePoint::createWithoutDimensions())];
            while ($stack !== []) {
                /** @var Node $node */
                $node = array_shift($stack);

                $lines[] = $this->jsonSerializeNodeAndDescendents(
                    $subgraph->findSubtree(
                        $node->aggregateId,
                        FindSubtreeFilter::create()
                    ),
                    $subgraph
                );
            }
        }

        return json_encode(
            $lines,
            JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function jsonSerializeNodeAndDescendents(Subtree $subtree, ContentSubgraphInterface $subgraph): array
    {
        $node = $subtree->node;

        $references = $subgraph->findReferences($node->aggregateId, FindReferencesFilter::create());

        $referencesArray = [];
        foreach ($references as $reference) {
            $referencesArray[$reference->name->value] ??= [];
            $referencesArray[$reference->name->value][] = array_filter([
                'node' => sprintf('Node(%s, %s)', $reference->node->aggregateId->value, $reference->node->nodeTypeName->value),
                'properties' => iterator_to_array($reference->properties ?? [])
            ]);
        }

        return array_filter([
            'id' => $node->aggregateId,
            'nodeTypeName' => $node->nodeTypeName,
            'nodeName' =>  $node->classification->isTethered() ? $node->name : null,
            'tags' => $node->tags->map(static fn (SubtreeTag $tag, bool $inherited) => ($inherited ? '*' : '') . strtoupper($tag->value)),
            'properties' => iterator_to_array($node->properties->serialized()),
            'references' => $referencesArray,
            'childNodes' => array_map(
                fn ($subtree) => $this->jsonSerializeNodeAndDescendents($subtree, $subgraph),
                iterator_to_array($subtree->children)
            )
        ]);
    }
}
