<?php

declare(strict_types=1);

namespace App\Controller;

use App\SessionBasedContentRepositoryFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class GetNodeTypes
{
    public function __construct(
        private SessionBasedContentRepositoryFactory $contentRepositoryFactory
    ) {
    }

    #[Route('/api/nodeTypes', methods: ['GET'])]
    public function __invoke(
        Request $request
    ): Response {
        $nodeTypesYaml = $this->contentRepositoryFactory->getNodeTypesYaml(
            $request->getSession(),
        );

        return new Response(
            $nodeTypesYaml
        );
    }
}
