<?php

declare(strict_types=1);

/*
 * This file is part of the UhifadhiLabs Fixtures Module.
 *
 * (c) Ezekiel Mjema <https://github.com/eemjema>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Uhifadhi\Fixtures;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Uhifadhi\Fixtures\Command\SeedAccountsCommand;
use Uhifadhi\Fixtures\Command\SeedAllCommand;
use Uhifadhi\Fixtures\Command\SeedAreaCommand;
use Uhifadhi\Fixtures\Command\SeedDepartmentsCommand;
use Uhifadhi\Fixtures\DependencyInjection\FixturesConfiguration;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * Fixtures — dev/demo data for uhifadhi hosts, kept OUT of the host so the app
 * carries no seed content: demo accounts + positions, an imaginary protected
 * area (nobody's land — Antarctica) with a fixed uuid, and the one-shot
 * baseline orchestrator. Deployment-specific seeding (real boundaries, real
 * uuids) passes explicit options; nothing here names a real deployment.
 *
 * Explicit wiring, no autowire/autoconfigure (reusable-bundle rule). Host
 * services are referenced by their class-name service ids — the bundle only
 * makes sense installed in a uhifadhi host, which provides them.
 */
final class UhifadhiFixturesBundle extends AbstractBundle
{
    protected string $extensionAlias = 'fixtures';

    public function configure(DefinitionConfigurator $definition): void
    {
        FixturesConfiguration::define($definition->rootNode());
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        $services->set('fixtures.command.accounts', SeedAccountsCommand::class)
            ->args([
                service('doctrine.orm.entity_manager'),
                service('Uhifadhi\Repository\UserRepository'),
                service('Uhifadhi\Repository\PositionRepository'),
                service('security.user_password_hasher'),
                $config['demo_password'],
                $config['super_admin_password'],
                $config['email_domain'],
            ])
            ->tag('console.command');

        $services->set('fixtures.command.departments', SeedDepartmentsCommand::class)
            ->args([
                service('doctrine.orm.entity_manager'),
                service('Uhifadhi\Repository\DepartmentRepository'),
                service('Uhifadhi\Service\DepartmentService'),
                service('Uhifadhi\Seam\Repository\ModuleRepository'),
                service('Uhifadhi\Repository\PositionRepository'),
            ])
            ->tag('console.command');

        $services->set('fixtures.command.area', SeedAreaCommand::class)
            ->args([service('Uhifadhi\Service\AreaSeeder')])
            ->tag('console.command');

        $services->set('fixtures.command.all', SeedAllCommand::class)
            ->tag('console.command');
    }
}
