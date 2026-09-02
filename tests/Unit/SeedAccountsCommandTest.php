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

namespace UhifadhiLabs\Fixtures\Tests\Unit;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Uhifadhi\Entity\User;
use Uhifadhi\Repository\PositionRepository;
use Uhifadhi\Repository\UserRepository;
use UhifadhiLabs\Fixtures\Command\SeedAccountsCommand;

/**
 * fixtures:accounts must refuse placeholder passwords, create the five demo
 * accounts when none exist, and be idempotent (create nothing when the emails
 * already resolve).
 */
final class SeedAccountsCommandTest extends TestCase
{
    private function command(UserRepository $users, ?EntityManagerInterface $em = null, string $demo = 'demo-pass', string $super = 'super-pass', string $domain = 'uhifadhi.test'): SeedAccountsCommand
    {
        $positions = $this->createStub(PositionRepository::class);
        $positions->method('findOneBy')->willReturn(null);

        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed');

        return new SeedAccountsCommand(
            $em ?? $this->createStub(EntityManagerInterface::class),
            $users,
            $positions,
            $hasher,
            $demo,
            $super,
            $domain,
        );
    }

    public function testItCreatesTheFiveDemoAccounts(): void
    {
        $users = $this->createStub(UserRepository::class);
        $users->method('findOneByEmail')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        // 3 positions + 5 users are persisted; one flush at the end.
        $em->expects(self::exactly(8))->method('persist');
        $em->expects(self::once())->method('flush');

        $tester = new CommandTester($this->command($users, $em));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('5 account(s) created', $tester->getDisplay());
    }

    public function testItIsIdempotentWhenAllAccountsExist(): void
    {
        $users = $this->createStub(UserRepository::class);
        $users->method('findOneByEmail')->willReturn(new User());

        $tester = new CommandTester($this->command($users));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('0 account(s) created', $tester->getDisplay());
    }

    public function testTheDemoEmailsUseTheConfiguredDomain(): void
    {
        $asked = [];
        $users = $this->createStub(UserRepository::class);
        $users->method('findOneByEmail')->willReturnCallback(
            static function (string $email) use (&$asked): ?User {
                $asked[] = $email;

                return null;
            },
        );

        $tester = new CommandTester($this->command($users, domain: 'demo.example.org'));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertSame([
            'superadmin@demo.example.org',
            'admin@demo.example.org',
            'manager@demo.example.org',
            'ranger@demo.example.org',
            'analyst@demo.example.org',
        ], $asked);
    }

    public function testAPlaceholderPasswordIsRefused(): void
    {
        $users = $this->createStub(UserRepository::class);
        $tester = new CommandTester($this->command($users, demo: 'changeme-in-env-local'));

        self::assertSame(Command::INVALID, $tester->execute([]));
        self::assertStringContainsString('DEMO_PASSWORD', $tester->getDisplay());
    }
}
