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

use Uhifadhi\Entity\Department;
use UhifadhiLabs\Trunk\Entity\Module;

/**
 * Dev-only stub of the host's DepartmentService — the fixtures commands are unit
 * tested against mocks of this surface.
 */
class DepartmentService
{
    public function create(string $name): Department
    {
        return new Department()->setName($name);
    }

    public function attachModule(Department $department, Module $module): void
    {
        $department->addModule($module);
    }
}
