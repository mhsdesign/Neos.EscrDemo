<?php

namespace App;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjectionFactory;
use Neos\ContentRepository\Core\Dimension\ConfigurationBasedContentDimensionSource;
use Neos\ContentRepository\Core\Factory\AuthProviderFactoryInterface;
use Neos\ContentRepository\Core\Factory\CommandHooksFactory;
use Neos\ContentRepository\Core\Factory\ContentRepositoryFactory;
use Neos\ContentRepository\Core\Factory\ContentRepositorySubscriberFactories;
use Neos\ContentRepository\Core\Feature\Security\AuthProviderInterface;
use Neos\ContentRepository\Core\Feature\Security\Dto\UserId;
use Neos\ContentRepository\Core\Feature\Security\StaticAuthProvider;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ArrayNormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\CollectionTypeDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ScalarNormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\UriNormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectArrayDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectBoolDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectFloatDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectIntDenormalizer;
use Neos\ContentRepository\Core\Infrastructure\Property\Normalizer\ValueObjectStringDenormalizer as ValueObjectStringDenormalizerAlias;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjectionFactoryInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\Subscription\Store\SubscriptionStoreInterface;
use Neos\ContentRepository\Dbal\SubscriptionStore\DoctrineSubscriptionStore;
use Neos\EventStore\DoctrineAdapter\DoctrineEventStore;
use Neos\EventStore\EventStoreInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;

final class ContentRepositoryFactoryBuilder
{
    public function __construct(
        private readonly Connection $dbalConnection
    ) {
    }

    /**
     * Must only be invoked once per process
     * @param array<string,mixed> $dimensionConfiguration
     * @param array<string,mixed> $nodeTypeConfiguration
     */
    public function createForId(
        ContentRepositoryId $contentRepositoryId,
        array $dimensionConfiguration,
        array $nodeTypeConfiguration,
    ): ContentRepositoryFactory {
        $clock = $this->buildClock();
        return new ContentRepositoryFactory(
            contentRepositoryId: $contentRepositoryId,
            eventStore: $this->buildEventStore($contentRepositoryId, $clock),
            nodeTypeManager: NodeTypeManager::createFromArrayConfiguration(
                $nodeTypeConfiguration
            ),
            contentDimensionSource: new ConfigurationBasedContentDimensionSource($dimensionConfiguration),
            propertySerializer: $this->buildPropertySerializer(),
            authProviderFactory: $this->buildAuthProviderFactory(),
            clock: $clock,
            subscriptionStore: $this->buildSubscriptionStore($contentRepositoryId, $clock),
            contentGraphProjectionFactory: $this->buildContentGraphProjectionFactory(),
            contentGraphCatchUpHookFactory: null,
            commandHooksFactory: new CommandHooksFactory(),
            additionalSubscriberFactories: ContentRepositorySubscriberFactories::createEmpty(),
            logger: null
        );
    }

    private function buildEventStore(ContentRepositoryId $contentRepositoryId, ClockInterface $clock): EventStoreInterface
    {
        return new DoctrineEventStore(
            $this->dbalConnection,
            'cr_' . $contentRepositoryId->value . '_events',
            $clock
        );
    }

    private function buildSubscriptionStore(ContentRepositoryId $contentRepositoryId, ClockInterface $clock): SubscriptionStoreInterface
    {
        return new DoctrineSubscriptionStore(sprintf('cr_%s_subscriptions', $contentRepositoryId->value), $this->dbalConnection, $clock);
    }

    private function buildContentGraphProjectionFactory(): ContentGraphProjectionFactoryInterface
    {
        return new DoctrineDbalContentGraphProjectionFactory(
            $this->dbalConnection
        );
    }

    private function buildPropertySerializer(): Serializer
    {
        $normalizers = [];

        $normalizers[] = new DateTimeNormalizer();
        $normalizers[] = new ScalarNormalizer();
        $normalizers[] = new BackedEnumNormalizer();
        $normalizers[] = new ArrayNormalizer();
        $normalizers[] = new UriNormalizer();
        $normalizers[] = new UriNormalizer();
        $normalizers[] = new ValueObjectArrayDenormalizer();
        $normalizers[] = new ValueObjectBoolDenormalizer();
        $normalizers[] = new ValueObjectFloatDenormalizer();
        $normalizers[] = new ValueObjectIntDenormalizer();
        $normalizers[] = new ValueObjectStringDenormalizerAlias();
        $normalizers[] = new CollectionTypeDenormalizer();

        return new Serializer($normalizers);
    }

    private function buildAuthProviderFactory(): AuthProviderFactoryInterface
    {
        return new class implements AuthProviderFactoryInterface
        {
            public function build(ContentRepositoryId $contentRepositoryId, ContentGraphReadModelInterface $contentGraphReadModel): AuthProviderInterface
            {
                return new StaticAuthProvider(UserId::forSystemUser());
            }
        };
    }

    private function buildClock(): ClockInterface
    {
        return new class implements ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable();
            }
        };
    }
}
