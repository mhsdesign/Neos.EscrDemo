<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class Index
{
    #[Route('/', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response(
            '<!DOCTYPE html>' .
            '<html>' .
            '<script defer src="_Resources/build/index.js"></script>' .
            '<link rel="stylesheet" href="_Resources/build/index.css"> ' .
            '<body</body>' .
            '</html>'
        );
    }
}
