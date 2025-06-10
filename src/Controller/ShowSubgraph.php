<?php

declare(strict_types=1);

namespace App\Controller;

use App\Arboretum;
use App\SessionBasedContentRepositoryFactory;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class ShowSubgraph
{
    public function __construct(
        private SessionBasedContentRepositoryFactory $contentRepositoryFactory
    ) {
    }

    #[Route('/api/subgraph/{workspaceName}/{dimensionSpacePoint}/{visibilityConstraints}', methods: ['GET'])]
    public function __invoke(string $workspaceName, string $dimensionSpacePoint, string $visibilityConstraints, Request $request): Response
    {
        $contentRepository = $this->contentRepositoryFactory->getOrSetup($request->getSession());

        try {
            $workspace = $contentRepository->getContentGraph(WorkspaceName::fromString($workspaceName));
        } catch (\Exception $exception) {
            return new Response(json_encode(['error' => ['message' => $exception->getMessage()]], JSON_THROW_ON_ERROR), status: 500);
        }

        $arboretum = new Arboretum(
            $workspace
        );

        try {
            $output = $arboretum->toAscii(
                DimensionSpacePoint::fromJsonString($dimensionSpacePoint),
                VisibilityConstraints::excludeSubtreeTags(
                    SubtreeTags::fromStrings(...json_decode($visibilityConstraints))
                )
            );
        } catch (\Exception $exception) {
            return new Response(json_encode(['error' => ['message' => $exception->getMessage()]], JSON_THROW_ON_ERROR), status: 500);
        }

        return new Response(
            json_encode(['success' => $output], JSON_THROW_ON_ERROR)
        );
    }
}
