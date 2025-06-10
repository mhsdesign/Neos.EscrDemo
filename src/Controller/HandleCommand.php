<?php

declare(strict_types=1);

namespace App\Controller;

use App\CommandHandler;
use App\StandaloneContentRepositoryRegistry;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class HandleCommand
{
    public function __construct(
        private StandaloneContentRepositoryRegistry $contentRepositoryRegistry
    ) {
    }

    #[Route('/api/command/{commandName}', methods: ['POST', 'GET'])]
    public function __invoke(
        string $commandName,
        #[MapQueryParameter] string $payload,
    ): Response {
        $contentRepository = $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString('default'));

        $commandHandler = new CommandHandler($contentRepository);

        $commandHandler->handleCommand(
            $commandName,
            json_decode($payload, true, 512, JSON_THROW_ON_ERROR)
        );

        return new Response();
    }
}
