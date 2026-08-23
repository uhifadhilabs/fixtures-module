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

namespace Uhifadhi\Entity;

/**
 * Dev-only stub of the host's AreaOfInterest entity — just the surface the
 * seeder commands touch. The real one is used inside uhifadhi.
 */
class AreaOfInterest
{
    public function __construct(
        private readonly ?string $name = null,
        private readonly ?string $uuid = null,
    ) {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getUuidString(): ?string
    {
        return $this->uuid;
    }
}
