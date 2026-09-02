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

namespace Uhifadhi\Entity;

/**
 * Dev-only stub of the host's Module entity — just the surface the fixtures
 * commands touch. The real one is used inside uhifadhi.
 */
class Module
{
    public function __construct(
        private readonly ?string $slug = null,
        private readonly ?string $name = null,
    ) {
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
