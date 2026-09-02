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

namespace Uhifadhi\Service;

use Uhifadhi\Entity\AreaOfInterest;

/**
 * Dev-only stub of the host's AreaSeeder — the fixtures commands are unit
 * tested against mocks of this surface.
 */
class AreaSeeder
{
    /**
     * @return array{AreaOfInterest, bool} the area and whether it was just created
     */
    public function ensureFromGeoJsonFile(string $uuid, string $name, string $file, string $source = 'seed'): array
    {
        return [new AreaOfInterest($name, $uuid), false];
    }
}
