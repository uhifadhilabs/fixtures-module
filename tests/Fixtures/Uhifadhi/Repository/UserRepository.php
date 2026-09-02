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

namespace Uhifadhi\Repository;

use Uhifadhi\Entity\User;

/**
 * Dev-only stub of the host's UserRepository — the fixtures commands are unit
 * tested against mocks of this surface.
 */
class UserRepository
{
    public function findOneByEmail(string $email): ?User
    {
        return null;
    }
}
