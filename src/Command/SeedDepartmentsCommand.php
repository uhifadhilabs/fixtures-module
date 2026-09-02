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

namespace UhifadhiLabs\Fixtures\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Uhifadhi\Entity\Department;
use Uhifadhi\Repository\DepartmentRepository;
use Uhifadhi\Repository\ModuleRepository;
use Uhifadhi\Repository\PositionRepository;
use Uhifadhi\Service\DepartmentService;

/**
 * Seeds the eight GENERIC demo departments — the same sample org the design
 * app renders (uhifadhi-web departments.widgets.js) — so a fresh host shows
 * the department lens working and matches the spec's boards. The names are
 * ordinary conservation vocabulary and never a real deployment's org chart;
 * a host that wants its own renames or adds them in the UI.
 *
 * Idempotent and non-destructive: nothing is ever purged, renamed or detached.
 * Modules are attached by slug only when the catalogue already holds them (a
 * missing slug is a note, never an error), and the demo positions are filed
 * only while nobody filed them — a position an admin already placed keeps that
 * placement, because the fixtures seed never overwrites a human decision. Patrols is
 * deliberately attached to BOTH Protection and Ecology: a module belongs to
 * every department that works in it, and the demo should show that.
 */
#[AsCommand(
    name: 'fixtures:departments',
    description: 'Seed the generic demo departments, their modules and the demo positions (idempotent).',
)]
final class SeedDepartmentsCommand extends Command
{
    /**
     * The demo org: department name => the catalogue slugs it works in.
     *
     * @var array<string, list<string>>
     */
    private const array DEPARTMENTS = [
        'Protection Service' => ['patrols'],
        'Ecology' => ['patrols'],
        'Community Development' => [],
        'Engineering' => [],
        'Human Resource' => [],
        'Planning' => [],
        'Tourism' => [],
        'ICT' => [],
    ];

    /**
     * Which demo position belongs under which department (fixtures:accounts creates them).
     *
     * @var array<string, string>
     */
    private const array POSITIONS = [
        'Ranger' => 'Protection Service',
        'Analyst' => 'Ecology',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DepartmentRepository $departments,
        private readonly DepartmentService $departmentService,
        private readonly ModuleRepository $modules,
        private readonly PositionRepository $positions,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var array<string, Department> $byName */
        $byName = [];
        $notes = [];
        $created = 0;
        $rows = [];

        foreach (self::DEPARTMENTS as $name => $slugs) {
            $department = $this->departments->findOneBy(['name' => $name]);
            if (null === $department) {
                // create() persists and flushes on the host's side.
                $department = $this->departmentService->create($name);
                ++$created;
                $state = 'created';
            } else {
                $state = 'existing';
            }
            $byName[$name] = $department;

            $attached = [];
            foreach ($slugs as $slug) {
                $module = $this->modules->findBySlug($slug);
                if (null === $module) {
                    $notes[] = \sprintf('Module "%s" is not in the catalogue — %s keeps its place; re-run once the module is installed.', $slug, $name);
                    continue;
                }
                if ($department->hasModule($module)) {
                    $attached[] = $slug.' (already)';
                    continue;
                }
                $this->departmentService->attachModule($department, $module);
                $attached[] = $slug;
            }

            $rows[] = [$name, $state, [] === $attached ? '—' : implode(', ', $attached)];
        }

        $io->table(['Department', 'State', 'Modules'], $rows);

        $filed = 0;
        $positionRows = [];
        foreach (self::POSITIONS as $positionName => $departmentName) {
            $position = $this->positions->findOneBy(['name' => $positionName]);
            if (null === $position) {
                $notes[] = \sprintf('Position "%s" does not exist yet — run fixtures:accounts first, then re-run this command.', $positionName);
                $positionRows[] = [$positionName, '—', 'missing'];
                continue;
            }

            $current = $position->getDepartment();
            if (null === $current) {
                $position->setDepartment($byName[$departmentName]);
                ++$filed;
                $positionRows[] = [$positionName, $departmentName, 'filed'];
                continue;
            }

            if ($current->getName() === $departmentName) {
                $positionRows[] = [$positionName, $departmentName, 'already filed'];
                continue;
            }

            $notes[] = \sprintf('Position "%s" is already filed under "%s" — left as is; the fixtures seed never overrides an admin\'s filing.', $positionName, $current->getName() ?? '?');
            $positionRows[] = [$positionName, $current->getName() ?? '?', 'left as is'];
        }

        if ($filed > 0) {
            $this->em->flush();
        }

        $io->table(['Position', 'Department', 'State'], $positionRows);

        foreach ($notes as $note) {
            $io->note($note);
        }

        $io->success(\sprintf('Departments seeded — %d department(s) created, %d position(s) filed; everything else was left untouched.', $created, $filed));

        return Command::SUCCESS;
    }
}
