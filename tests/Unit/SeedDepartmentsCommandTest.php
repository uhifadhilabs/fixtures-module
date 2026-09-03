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

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Uhifadhi\Entity\Department;
use Uhifadhi\Entity\Position;
use Uhifadhi\Fixtures\Command\SeedDepartmentsCommand;
use Uhifadhi\Repository\DepartmentRepository;
use Uhifadhi\Repository\PositionRepository;
use Uhifadhi\Service\DepartmentService;
use Uhifadhi\Trunk\Entity\Module;
use Uhifadhi\Trunk\Repository\ModuleRepository;

/**
 * fixtures:departments must create the three generic demo departments when none
 * exist, be idempotent, attach catalogue modules by slug (skipping the ones the
 * host has not installed), and file only the demo positions nobody filed yet —
 * an admin's own filing is never overwritten.
 */
final class SeedDepartmentsCommandTest extends TestCase
{
    /**
     * @param array<string, Department> $existing  departments already in the database, by name
     * @param array<string, Module>     $catalogue modules already in the catalogue, by slug
     * @param array<string, Position>   $positions positions already in the database, by name
     */
    private function command(
        array $existing = [],
        array $catalogue = [],
        array $positions = [],
        ?EntityManagerInterface $em = null,
        ?DepartmentService $service = null,
    ): SeedDepartmentsCommand {
        $departmentRepository = $this->createStub(DepartmentRepository::class);
        $departmentRepository->method('findOneBy')->willReturnCallback(
            /** @param array<string, mixed> $criteria */
            static function (array $criteria) use (&$existing): ?Department {
                $name = $criteria['name'] ?? null;

                return \is_string($name) ? ($existing[$name] ?? null) : null;
            },
        );

        $moduleRepository = $this->createStub(ModuleRepository::class);
        $moduleRepository->method('findBySlug')->willReturnCallback(
            static fn (string $slug): ?Module => $catalogue[$slug] ?? null,
        );

        $positionRepository = $this->createStub(PositionRepository::class);
        $positionRepository->method('findOneBy')->willReturnCallback(
            /** @param array<string, mixed> $criteria */
            static function (array $criteria) use ($positions): ?Position {
                $name = $criteria['name'] ?? null;

                return \is_string($name) ? ($positions[$name] ?? null) : null;
            },
        );

        if (null === $service) {
            $service = $this->createStub(DepartmentService::class);
            $service->method('create')->willReturnCallback(
                static function (string $name) use (&$existing): Department {
                    $department = new Department()->setName($name);
                    $existing[$name] = $department;

                    return $department;
                },
            );
            $service->method('attachModule')->willReturnCallback(
                static fn (Department $department, Module $module) => $department->addModule($module),
            );
        }

        return new SeedDepartmentsCommand(
            $em ?? $this->createStub(EntityManagerInterface::class),
            $departmentRepository,
            $service,
            $moduleRepository,
            $positionRepository,
        );
    }

    private function department(string $name): Department
    {
        return new Department()->setName($name);
    }

    /**
     * The full demo roster, in creation order — the design app's sample org
     * (departments.widgets.js), which the seeded host must mirror.
     *
     * @return list<string>
     */
    private static function roster(): array
    {
        return ['Protection Service', 'Ecology', 'Community Development', 'Engineering', 'Human Resource', 'Planning', 'Tourism', 'ICT'];
    }

    /** @return array<string, Department> */
    private function allExisting(): array
    {
        $existing = [];
        foreach (self::roster() as $name) {
            $existing[$name] = $this->department($name);
        }

        return $existing;
    }

    public function testItCreatesTheEightDemoDepartmentsWhenNoneExist(): void
    {
        $created = [];
        $service = $this->createStub(DepartmentService::class);
        $service->method('create')->willReturnCallback(
            static function (string $name) use (&$created): Department {
                $created[] = $name;

                return new Department()->setName($name);
            },
        );

        $tester = new CommandTester($this->command(service: $service));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertSame(self::roster(), $created);

        $display = $tester->getDisplay();
        foreach (self::roster() as $name) {
            self::assertStringContainsString($name, $display);
        }
        self::assertStringContainsString('8 department(s) created', $display);
    }

    public function testASecondRunCreatesNothing(): void
    {
        $service = $this->createMock(DepartmentService::class);
        $service->expects(self::never())->method('create');

        $existing = $this->allExisting();

        $tester = new CommandTester($this->command(existing: $existing, service: $service));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('0 department(s) created', $tester->getDisplay());
    }

    public function testItAttachesTheCatalogueModuleToBothProtectionAndEcology(): void
    {
        $patrols = new Module()->setSlug('patrols')->setName('Patrols');

        $attached = [];
        $service = $this->createStub(DepartmentService::class);
        $service->method('create')->willReturnCallback(
            static fn (string $name): Department => new Department()->setName($name),
        );
        $service->method('attachModule')->willReturnCallback(
            static function (Department $department, Module $module) use (&$attached): void {
                $attached[] = $department->getName().'/'.$module->getSlug();
                $department->addModule($module);
            },
        );

        $tester = new CommandTester($this->command(catalogue: ['patrols' => $patrols], service: $service));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertSame(['Protection Service/patrols', 'Ecology/patrols'], $attached);
    }

    public function testAnAlreadyAttachedModuleIsNotAttachedTwice(): void
    {
        $patrols = new Module()->setSlug('patrols')->setName('Patrols');

        $service = $this->createMock(DepartmentService::class);
        $service->expects(self::never())->method('attachModule');

        $existing = $this->allExisting();
        $existing['Protection Service']->addModule($patrols);
        $existing['Ecology']->addModule($patrols);

        $tester = new CommandTester($this->command(existing: $existing, catalogue: ['patrols' => $patrols], service: $service));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    public function testAMissingSlugIsSkippedWithANoteAndNeverFails(): void
    {
        $service = $this->createMock(DepartmentService::class);
        $service->method('create')->willReturnCallback(
            static fn (string $name): Department => new Department()->setName($name),
        );
        $service->expects(self::never())->method('attachModule');

        $tester = new CommandTester($this->command(service: $service));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('patrols', $tester->getDisplay());
        self::assertStringContainsString('not in the catalogue', $tester->getDisplay());
    }

    public function testItFilesTheUnfiledDemoPositions(): void
    {
        $ranger = new Position()->setName('Ranger');
        $analyst = new Position()->setName('Analyst');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $tester = new CommandTester($this->command(
            positions: ['Ranger' => $ranger, 'Analyst' => $analyst],
            em: $em,
        ));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertSame('Protection Service', $ranger->getDepartment()?->getName());
        self::assertSame('Ecology', $analyst->getDepartment()?->getName());
    }

    public function testAPositionFiledElsewhereIsLeftAloneWithANote(): void
    {
        $tourism = $this->department('Tourism');
        $ranger = new Position()->setName('Ranger')->setDepartment($tourism);

        $tester = new CommandTester($this->command(positions: ['Ranger' => $ranger]));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertSame($tourism, $ranger->getDepartment());
        self::assertStringContainsString('Tourism', $tester->getDisplay());
    }

    public function testAMissingPositionIsReportedButNeverFails(): void
    {
        $tester = new CommandTester($this->command());

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('Ranger', $tester->getDisplay());
        self::assertStringContainsString('fixtures:accounts', $tester->getDisplay());
    }
}
