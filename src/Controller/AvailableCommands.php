<?php

declare(strict_types=1);

namespace App\Controller;

use App\CommandHandler;
use App\StandaloneContentRepositoryRegistry;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class AvailableCommands
{
    public function __construct(
        private StandaloneContentRepositoryRegistry $contentRepositoryRegistry
    ) {
    }

    #[Route('/api/commands', methods: ['GET'])]
    public function __invoke(): Response
    {
        $contentRepository = $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString('default'));

        $commandHandler = new CommandHandler($contentRepository);

        $output = [];
        foreach ($commandHandler->getAvailableCommands() as $availableCommandName) {
            $output[$availableCommandName] = $commandHandler->getCommandOptions($availableCommandName);
        }

        return new Response(content: json_encode($output, JSON_THROW_ON_ERROR));
    }
}
