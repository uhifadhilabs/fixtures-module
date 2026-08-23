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

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Uhifadhi\Enum\TeamRoleEnum;

/**
 * Dev-only stub of the host's User entity — just the surface the seeder
 * commands touch. The real one is used inside uhifadhi.
 */
class User implements PasswordAuthenticatedUserInterface
{
    private ?string $email = null;
    private ?string $firstName = null;
    private ?string $lastName = null;
    private ?TeamRoleEnum $teamRole = null;
    private ?Position $position = null;
    private bool $verified = false;
    private ?string $password = null;

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setTeamRole(TeamRoleEnum $teamRole): static
    {
        $this->teamRole = $teamRole;

        return $this;
    }

    public function getTeamRole(): ?TeamRoleEnum
    {
        return $this->teamRole;
    }

    public function setPosition(?Position $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    public function setVerified(bool $verified): static
    {
        $this->verified = $verified;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }
}
