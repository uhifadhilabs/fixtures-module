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
 * Dev-only stub of the host's Department entity — just the surface the fixtures
 * commands touch. The real one is used inside uhifadhi.
 */
class Department
{
    private ?string $name = null;

    /** @var list<Module> */
    private array $modules = [];

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return list<Module>
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    public function hasModule(Module $module): bool
    {
        return \in_array($module, $this->modules, true);
    }

    public function addModule(Module $module): static
    {
        if (!$this->hasModule($module)) {
            $this->modules[] = $module;
        }

        return $this;
    }
}
