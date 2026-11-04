<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('evolutive_sylius_wishlist');

        $treeBuilder->getRootNode()
            ->children()
                ->integerNode('guest_ttl')
                    ->info('Days a guest wishlist may go without changes before the purge command deletes it.')
                    ->defaultValue(30)
                    ->min(1)
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
