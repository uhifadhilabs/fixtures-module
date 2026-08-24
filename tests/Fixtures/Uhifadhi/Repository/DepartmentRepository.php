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

use Uhifadhi\Entity\Department;

/**
 * Dev-only stub of the host's DepartmentRepository — the seeder commands are
 * unit tested against mocks of this surface.
 */
class DepartmentRepository
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function findOneBy(array $criteria): ?Department
    {
        return null;
    }
}
