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

namespace Uhifadhi\Fixtures\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Uhifadhi\Fixtures\DependencyInjection\FixturesConfiguration;

/**
 * The fixtures config tree: env-backed password defaults, a neutral demo email
 * domain, and no empty domain (it would produce invalid demo emails).
 */
final class FixturesConfigurationTest extends TestCase
{
    /**
     * @param list<array<string, mixed>> $configs
     *
     * @return array<mixed>
     */
    private function process(array $configs): array
    {
        $tree = new TreeBuilder('fixtures');
        FixturesConfiguration::define($tree->getRootNode());

        return new Processor()->process($tree->buildTree(), $configs);
    }

    public function testItDefaultsToEnvPasswordsAndTheNeutralDomain(): void
    {
        $config = $this->process([[]]);

        self::assertSame('%env(DEMO_PASSWORD)%', $config['demo_password']);
        self::assertSame('%env(DEMO_SUPER_ADMIN_PASSWORD)%', $config['super_admin_password']);
        self::assertSame('uhifadhi.test', $config['email_domain']);
    }

    public function testAHostCanOverrideEveryValue(): void
    {
        $config = $this->process([[
            'demo_password' => 'demo-pass',
            'super_admin_password' => 'super-pass',
            'email_domain' => 'demo.example.org',
        ]]);

        self::assertSame('demo-pass', $config['demo_password']);
        self::assertSame('super-pass', $config['super_admin_password']);
        self::assertSame('demo.example.org', $config['email_domain']);
    }

    public function testAnEmptyEmailDomainIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process([['email_domain' => '']]);
    }
}
