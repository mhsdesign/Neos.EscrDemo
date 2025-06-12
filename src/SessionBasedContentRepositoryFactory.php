<?php

declare(strict_types=1);

namespace App;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainerFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Yaml\Yaml;

final class SessionBasedContentRepositoryFactory
{
    public function __construct(
        private ContentRepositoryFactoryBuilder $contentRepositoryFactoryBuilder
    ) {
    }

    public function getOrSetup(SessionInterface $session): ContentRepository
    {
        $isNewSession = !$session->isStarted();

        $session->start();
        $contentRepositoryId = $this->getContentRepositoryIdForSession($session);

        $contentRepositoryFactory = $this->contentRepositoryFactoryBuilder->createForId(
            $contentRepositoryId,
            dimensionConfiguration: $session->get('dimensions', []),
            nodeTypeConfiguration: Yaml::parse($this->getNodeTypesYaml($session))
        );

        if ($isNewSession) {
            $crMaintainer = $contentRepositoryFactory->buildService(new ContentRepositoryMaintainerFactory());
            $crMaintainer->setUp();
        }

        return $contentRepositoryFactory->getOrBuild();
    }

    public function setNodeTypesYaml(SessionInterface $session, string $nodeTypesYaml): void
    {
        $session->set('nodeTypes', $nodeTypesYaml);
    }

    public function getNodeTypesYaml(SessionInterface $session): string
    {
        return $session->get('nodeTypes', <<<YAML
        # adjust the NodeType configuration

        "My.Custom:Root":
            superTypes:
                "Neos.ContentRepository:Root": true

        "My.Custom:Node":
            properties:
                title:
                    type: string


        YAML);
    }

    private function getContentRepositoryIdForSession(SessionInterface $session): ContentRepositoryId
    {
        if ($session->get('contentRepository')) {
            return ContentRepositoryId::fromString($session->get('contentRepository'));
        }

        // todo track separately in database via auto incrementer and also garbage collect old tables when session is destroyed
        $id = preg_replace('/[^a-z\d_]/', '', $session->getId()) ?: '';
        preg_match('/[a-z][a-z\d_]{0,13}[a-z]/', $id, $matches);
        $contentRepositoryId =  ContentRepositoryId::fromString($matches[0] ?? '');

        $session->set('contentRepository', $contentRepositoryId->value);
        return $contentRepositoryId;
    }
}
