<?php

declare(strict_types=1);

namespace App\Controller;

use App\CommandHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class AvailableCommands
{
    public function __construct(
        private CommandHelper $commandHelper
    ) {
    }

    #[Route('/api/commands', methods: ['GET'])]
    public function __invoke(): Response
    {
        $output = [];
        foreach ($this->commandHelper->getAvailableCommands() as $availableCommandName) {
            $output[$availableCommandName] = $this->commandHelper->getCommandOptions($availableCommandName);
        }

        return new Response(content: json_encode($output, JSON_THROW_ON_ERROR));
    }
}
