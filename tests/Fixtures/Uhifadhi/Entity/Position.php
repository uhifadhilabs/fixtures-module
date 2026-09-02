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

use Uhifadhi\Enum\PermissionEnum;

/**
 * Dev-only stub of the host's Position entity — just the surface the fixtures
 * commands touch. The real one is used inside uhifadhi.
 */
class Position
{
    private ?string $name = null;

    /** @var list<PermissionEnum> */
    private array $permissions = [];

    private ?Department $department = null;

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param list<PermissionEnum> $permissions
     */
    public function setPermissions(array $permissions): static
    {
        $this->permissions = $permissions;

        return $this;
    }

    /**
     * @return list<PermissionEnum>
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): static
    {
        $this->department = $department;

        return $this;
    }
}
