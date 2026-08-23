<?php

declare(strict_types=1);

/*
 * This file is part of the UhifadhiLabs Seeder Module.
 *
 * (c) Ezekiel Mjema <https://github.com/eemjema>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace UhifadhiLabs\Seeder\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

/**
 * The bundle's semantic configuration — how a host points the demo seed at its
 * own credentials and domain in config/packages/seeder.yaml:
 *
 *   seeder:
 *     demo_password: '%env(DEMO_PASSWORD)%'
 *     super_admin_password: '%env(DEMO_SUPER_ADMIN_PASSWORD)%'
 *     email_domain: 'uhifadhi.test'
 *
 * Static so the tree is testable with a plain Processor and shared verbatim by
 * the bundle's configure() hook.
 */
final class SeederConfiguration
{
    public static function define(NodeDefinition|ArrayNodeDefinition $root): void
    {
        if (!$root instanceof ArrayNodeDefinition) {
            throw new \LogicException('The seeder root node must be an array node.');
        }

        $root
            ->children()
                ->scalarNode('demo_password')
                    ->info('Shared password for the demo tier accounts. The command refuses placeholder values.')
                    ->defaultValue('%env(DEMO_PASSWORD)%')
                ->end()
                ->scalarNode('super_admin_password')
                    ->info('Distinct password for the Super Admin account, which can impersonate anyone.')
                    ->defaultValue('%env(DEMO_SUPER_ADMIN_PASSWORD)%')
                ->end()
                ->scalarNode('email_domain')
                    ->info('Domain of the generated demo emails (superadmin@, admin@, manager@, ranger@, analyst@).')
                    ->defaultValue('uhifadhi.test')->cannotBeEmpty()
                ->end()
            ->end();
    }
}
