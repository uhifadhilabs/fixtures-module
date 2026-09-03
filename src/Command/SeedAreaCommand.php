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

namespace Uhifadhi\Fixtures\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Uhifadhi\Service\AreaSeeder;

/**
 * Seeds an Area (AreaOfInterest) with a FIXED uuid so config addressing it by
 * uuid resolves after every wipe. Idempotent: re-running is a no-op once the
 * area exists. Without options it seeds the built-in IMAGINARY demo area — a
 * fictional protected area on the Antarctic coast (land owned by nobody), so
 * the module never references a real deployment. Real deployments pass
 * explicit --uuid/--name/--file.
 */
#[AsCommand(
    name: 'fixtures:area',
    description: 'Seed an area with a fixed uuid — the imaginary demo area by default, or an explicit GeoJSON boundary.',
)]
final class SeedAreaCommand extends Command
{
    /** Fixed so re-seeding after a wipe reproduces the same area uuid. */
    public const string DEMO_UUID = 'd9c3a97e-4b3f-4c1e-8a2a-7f2e01c0ffee';
    public const string DEMO_NAME = 'Aurora Basin Protected Area';

    public function __construct(
        private readonly AreaSeeder $seeder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('uuid', null, InputOption::VALUE_REQUIRED, 'Fixed uuid for the area', self::DEMO_UUID)
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Area name', self::DEMO_NAME)
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'GeoJSON boundary file (defaults to the built-in imaginary boundary)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $uuid = $input->getOption('uuid');
        $name = $input->getOption('name');
        $uuid = \is_string($uuid) ? $uuid : self::DEMO_UUID;
        $name = \is_string($name) ? $name : self::DEMO_NAME;
        $file = $input->getOption('file');
        $builtIn = null;

        if (!\is_string($file) || '' === $file) {
            $file = $builtIn = $this->writeBuiltInBoundary();
        }

        try {
            [$area, $created] = $this->seeder->ensureFromGeoJsonFile($uuid, $name, $file);
        } catch (\RuntimeException|\InvalidArgumentException|\JsonException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        } finally {
            if (null !== $builtIn) {
                @unlink($builtIn);
            }
        }

        $io->success(\sprintf(
            '%s area "%s" (%s).',
            $created ? 'Created' : 'Already present:',
            $area->getName() ?? $name,
            $area->getUuidString() ?? $uuid,
        ));

        return Command::SUCCESS;
    }

    /**
     * The imaginary boundary: a rectangle on the Queen Maud Land coast of
     * Antarctica — territory under no national ownership, so the demo area can
     * never be mistaken for (or leak) a real client deployment.
     */
    private function writeBuiltInBoundary(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'fixtures-area').'.geojson';
        file_put_contents($file, (string) json_encode([
            'type' => 'Feature',
            'properties' => ['name' => self::DEMO_NAME],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [5.0, -72.5],
                    [15.0, -72.5],
                    [15.0, -70.0],
                    [5.0, -70.0],
                    [5.0, -72.5],
                ]],
            ],
        ], \JSON_THROW_ON_ERROR));

        return $file;
    }
}
