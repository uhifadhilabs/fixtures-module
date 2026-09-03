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
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Uhifadhi\Fixtures\Command\SeedAllCommand;

/**
 * fixtures:all orchestrates accounts → area (forwarding --area-* options) and
 * runs the host's catalogue step only when the host provides it — the module
 * must degrade gracefully outside a full uhifadhi host.
 */
final class SeedAllCommandTest extends TestCase
{
    /** @var list<string> */
    private array $ran = [];

    /** @var array<string, mixed> */
    private array $areaInput = [];

    private function application(bool $withCatalogue, bool $withDepartments = true): Application
    {
        $application = new Application();
        $application->setAutoExit(false);

        $record = static fn (string $name, callable $onRun): Command => new class($name, $onRun) extends Command {
            /** @param callable(InputInterface): void $onRun */
            public function __construct(string $name, private $onRun)
            {
                parent::__construct($name);
                $this->addOption('uuid', null, InputOption::VALUE_REQUIRED)
                    ->addOption('name', null, InputOption::VALUE_REQUIRED)
                    ->addOption('file', null, InputOption::VALUE_REQUIRED);
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                ($this->onRun)($input);

                return Command::SUCCESS;
            }
        };

        $application->addCommand($record('fixtures:accounts', function (): void {
            $this->ran[] = 'fixtures:accounts';
        }));
        $application->addCommand($record('fixtures:area', function (InputInterface $input): void {
            $this->ran[] = 'fixtures:area';
            $this->areaInput = ['uuid' => $input->getOption('uuid'), 'file' => $input->getOption('file')];
        }));
        if ($withCatalogue) {
            $application->addCommand($record('app:seed:catalogue', function (): void {
                $this->ran[] = 'app:seed:catalogue';
            }));
        }
        if ($withDepartments) {
            $application->addCommand($record('fixtures:departments', function (): void {
                $this->ran[] = 'fixtures:departments';
            }));
        }
        $application->addCommand(new SeedAllCommand());

        return $application;
    }

    public function testItRunsAllStepsInOrderAndForwardsAreaOptions(): void
    {
        $application = $this->application(withCatalogue: true);
        $tester = new CommandTester($application->find('fixtures:all'));

        $exit = $tester->execute(['--area-uuid' => 'abc', '--area-file' => '/tmp/x.geojson']);

        self::assertSame(Command::SUCCESS, $exit);
        // Departments run last: they attach modules by slug, so the catalogue must exist first.
        self::assertSame(['fixtures:accounts', 'fixtures:area', 'app:seed:catalogue', 'fixtures:departments'], $this->ran);
        self::assertSame(['uuid' => 'abc', 'file' => '/tmp/x.geojson'], $this->areaInput);
    }

    public function testTheCatalogueStepIsSkippedOutsideAHost(): void
    {
        $application = $this->application(withCatalogue: false, withDepartments: false);
        $tester = new CommandTester($application->find('fixtures:all'));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertSame(['fixtures:accounts', 'fixtures:area'], $this->ran);
    }
}
