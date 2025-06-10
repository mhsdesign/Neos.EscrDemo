<?php

declare(strict_types=1);

namespace App;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
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
                $lines[] = "Node ($node->nodeTypeName) $node->aggregateId";
                // use subtree
                array_push($stack, ...iterator_to_array($subgraph->findChildNodes($node->aggregateId, FindChildNodesFilter::create())));
            }
        }

        return join("\n", $lines);
    }
}
