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

namespace Uhifadhi\Repository;

use Uhifadhi\Entity\Position;

/**
 * Dev-only stub of the host's PositionRepository — the seeder commands are
 * unit tested against mocks of this surface.
 */
class PositionRepository
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function findOneBy(array $criteria): ?Position
    {
        return null;
    }
}
