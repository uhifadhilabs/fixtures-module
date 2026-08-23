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

namespace UhifadhiLabs\Seeder\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Uhifadhi\Entity\AreaOfInterest;
use Uhifadhi\Service\AreaSeeder;
use UhifadhiLabs\Seeder\Command\SeedAreaCommand;

/**
 * seeder:area seeds the built-in IMAGINARY Antarctic demo area by default (no
 * real-deployment references in the module), forwards explicit options
 * untouched, and surfaces seeder failures as command failures.
 */
final class SeedAreaCommandTest extends TestCase
{
    public function testTheDefaultIsTheImaginaryAntarcticArea(): void
    {
        $seeder = $this->createMock(AreaSeeder::class);
        $seeder->expects(self::once())->method('ensureFromGeoJsonFile')
            ->willReturnCallback(static function (string $uuid, string $name, string $file): array {
                self::assertSame(SeedAreaCommand::DEMO_UUID, $uuid);
                self::assertSame(SeedAreaCommand::DEMO_NAME, $name);
                $boundary = (string) file_get_contents($file);
                self::assertStringContainsString('-72.5', $boundary, 'built-in boundary sits on the Antarctic coast');

                return [new AreaOfInterest($name, $uuid), true];
            });

        $tester = new CommandTester(new SeedAreaCommand($seeder));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('Created', $tester->getDisplay());
        self::assertStringContainsString(SeedAreaCommand::DEMO_NAME, $tester->getDisplay());
    }

    public function testExplicitOptionsAreForwardedUntouched(): void
    {
        $seeder = $this->createMock(AreaSeeder::class);
        $seeder->expects(self::once())->method('ensureFromGeoJsonFile')
            ->with('11111111-2222-4333-8444-555555555555', 'Real Park', '/tmp/real.geojson')
            ->willReturn([new AreaOfInterest('Real Park', '11111111-2222-4333-8444-555555555555'), false]);

        $tester = new CommandTester(new SeedAreaCommand($seeder));

        $exit = $tester->execute([
            '--uuid' => '11111111-2222-4333-8444-555555555555',
            '--name' => 'Real Park',
            '--file' => '/tmp/real.geojson',
        ]);

        self::assertSame(Command::SUCCESS, $exit);
        self::assertStringContainsString('Already present', $tester->getDisplay());
    }

    public function testASeederFailureFailsTheCommand(): void
    {
        $seeder = $this->createStub(AreaSeeder::class);
        $seeder->method('ensureFromGeoJsonFile')->willThrowException(new \RuntimeException('Cannot read GeoJSON file'));

        $tester = new CommandTester(new SeedAreaCommand($seeder));

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Cannot read', $tester->getDisplay());
    }
}
