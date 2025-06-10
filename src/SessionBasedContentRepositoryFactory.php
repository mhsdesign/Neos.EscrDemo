<?php

declare(strict_types=1);

namespace App;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainerFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Symfony\Component\HttpFoundation\Session\Session;

final class SessionBasedContentRepositoryFactory
{
    public function __construct(
        private ContentRepositoryFactoryBuilder $contentRepositoryFactoryBuilder
    ) {
    }

    public function getOrSetup(Session $session): ContentRepository
    {
        $needsSetup = !$session->isStarted();

        $session->start();


        // todo track separately in database via auto incrementer and also garbage collect old tables when session is destroyed
        $id = preg_replace('/[^a-z\d_]/', '', $session->getId());
        preg_match('/[a-z][a-z\d_]{0,13}[a-z]/', $id, $matches);

        $session->set('crid', $id);

        $contentRepositoryFactory = $this->contentRepositoryFactoryBuilder->createForId(ContentRepositoryId::fromString($matches[0]));
        if ($needsSetup) {
            $crMaintainer = $contentRepositoryFactory->buildService(new ContentRepositoryMaintainerFactory());
            $crMaintainer->setUp();
        }

        return $contentRepositoryFactory->getOrBuild();
    }
}
