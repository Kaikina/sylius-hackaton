<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class EvolutiveSyliusWishlistExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{guest_ttl: int} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('evolutive_sylius_wishlist.guest_ttl', $config['guest_ttl']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.xml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineMigrations($container);

        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'entity_managers' => [
                    'default' => [
                        'mappings' => [
                            'EvolutiveSyliusWishlistPlugin' => [
                                'type' => 'attribute',
                                'is_bundle' => false,
                                'dir' => \dirname(__DIR__) . '/Entity',
                                'prefix' => 'Evolutive\SyliusWishlistPlugin\Entity',
                                'alias' => 'EvolutiveSyliusWishlist',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $container->prependExtensionConfig('ux_icons', [
            'icon_sets' => [
                'evolutive-wishlist' => ['path' => \dirname(__DIR__, 2) . '/assets/icons'],
            ],
        ]);
    }

    protected function getMigrationsNamespace(): string
    {
        return 'Evolutive\SyliusWishlistPlugin\Migrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@EvolutiveSyliusWishlistPlugin/src/Migrations';
    }

    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return [
            'Sylius\Bundle\CoreBundle\Migrations',
        ];
    }
}
