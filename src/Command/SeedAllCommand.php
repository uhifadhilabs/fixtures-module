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

namespace UhifadhiLabs\Seeder\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Rebuilds the demo baseline in one shot: accounts → area → the host's module
 * catalogue (which needs the area to backfill its modules). Without options the
 * area step seeds the imaginary demo area; pass --area-uuid/--area-name/
 * --area-file to seed a real one. Each installed module seeds its own data with
 * its own <module>:seed:* commands — run those after this.
 */
#[AsCommand(
    name: 'seeder:all',
    description: 'Seed the baseline (accounts, area, catalogue). Modules seed themselves via <module>:seed:*.',
)]
final class SeedAllCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('area-uuid', null, InputOption::VALUE_REQUIRED, 'Fixed uuid passed to seeder:area')
            ->addOption('area-name', null, InputOption::VALUE_REQUIRED, 'Area name passed to seeder:area')
            ->addOption('area-file', null, InputOption::VALUE_REQUIRED, 'GeoJSON boundary file passed to seeder:area');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $application = $this->getApplication();
        if (null === $application) {
            return Command::FAILURE;
        }

        $areaOptions = [];
        foreach (['area-uuid' => '--uuid', 'area-name' => '--name', 'area-file' => '--file'] as $own => $forwarded) {
            $value = $input->getOption($own);
            if (\is_string($value) && '' !== $value) {
                $areaOptions[$forwarded] = $value;
            }
        }

        $steps = [
            'seeder:accounts' => [],
            'seeder:area' => $areaOptions,
        ];
        // The catalogue command belongs to the host; skip gracefully when the
        // module runs outside a full uhifadhi host (e.g. its own test kernel).
        if ($application->has('app:seed:catalogue')) {
            $steps['app:seed:catalogue'] = [];
        }

        foreach ($steps as $name => $options) {
            $io->section($name);
            $code = $application->find($name)->run(new ArrayInput($options), $output);
            if (Command::SUCCESS !== $code) {
                $io->error(\sprintf('Step "%s" failed (exit %d) — stopping.', $name, $code));

                return $code;
            }
        }

        $io->success('Baseline fully seeded.');

        return Command::SUCCESS;
    }
}
