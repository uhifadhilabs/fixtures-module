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

namespace UhifadhiLabs\Seeder\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Uhifadhi\Entity\Position;
use Uhifadhi\Entity\User;
use Uhifadhi\Enum\PermissionEnum;
use Uhifadhi\Enum\TeamRoleEnum;
use Uhifadhi\Repository\PositionRepository;
use Uhifadhi\Repository\UserRepository;

/**
 * Seeds the demo accounts (Super Admin / Admin / Manager / two Staff) and their
 * positions with a single shared password from DEMO_PASSWORD, hashed here.
 * Idempotent and non-destructive: it never purges, so it is safe against a
 * database holding real data. Demo emails are built from the configured
 * seeder.email_domain (uhifadhi.test by default); provision real accounts with
 * the host's app:user:create.
 */
#[AsCommand(
    name: 'seeder:accounts',
    description: 'Seed the demo accounts + positions (idempotent; password from DEMO_PASSWORD).',
)]
final class SeedAccountsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly PositionRepository $positions,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly string $demoPassword,
        // The Super Admin can impersonate anyone, so it gets its own distinct password.
        private readonly string $superAdminPassword,
        private readonly string $emailDomain,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        foreach (['DEMO_PASSWORD' => $this->demoPassword, 'DEMO_SUPER_ADMIN_PASSWORD' => $this->superAdminPassword] as $name => $value) {
            if ('' === trim($value) || 'changeme-in-env-local' === $value) {
                $io->error(\sprintf('Set a real %s in .env.local before seeding demo accounts.', $name));

                return Command::INVALID;
            }
        }

        // Positions first — Manager and Staff are position-driven (only Admin+ hold
        // permissions by tier), so every non-admin demo account references one.
        $parkManager = $this->ensurePosition('Park Manager', PermissionEnum::cases());
        $ranger = $this->ensurePosition('Ranger', [PermissionEnum::AreaView, PermissionEnum::IngestionRun]);
        $analyst = $this->ensurePosition('Analyst', [PermissionEnum::AreaView, PermissionEnum::ModuleView, PermissionEnum::ModuleCreate]);

        $created = 0;
        $created += $this->ensureUser($this->email('superadmin'), 'Sofia', 'Marwa', TeamRoleEnum::SuperAdmin, password: $this->superAdminPassword);
        $created += $this->ensureUser($this->email('admin'), 'Amina', 'Hassan', TeamRoleEnum::Admin);
        $created += $this->ensureUser($this->email('manager'), 'Joseph', 'Kimaro', TeamRoleEnum::Manager, $parkManager);
        $created += $this->ensureUser($this->email('ranger'), 'Neema', 'Kileo', TeamRoleEnum::Staff, $ranger);
        $created += $this->ensureUser($this->email('analyst'), 'Baraka', 'Mushi', TeamRoleEnum::Staff, $analyst);

        $this->em->flush();

        $io->success(\sprintf('Demo seed complete — %d account(s) created, the rest already existed.', $created));
        $io->note('Log in with any demo email and the DEMO_PASSWORD value.');

        return Command::SUCCESS;
    }

    private function email(string $localPart): string
    {
        return $localPart.'@'.$this->emailDomain;
    }

    /**
     * @param list<PermissionEnum> $permissions
     */
    private function ensurePosition(string $name, array $permissions): Position
    {
        $position = $this->positions->findOneBy(['name' => $name]);
        if (null === $position) {
            $position = new Position()->setName($name)->setPermissions($permissions);
            $this->em->persist($position);
        }

        return $position;
    }

    private function ensureUser(string $email, string $firstName, string $lastName, TeamRoleEnum $tier, ?Position $position = null, ?string $password = null): int
    {
        if (null !== $this->users->findOneByEmail($email)) {
            return 0;
        }

        $user = new User()
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setTeamRole($tier)
            ->setPosition($position)
            ->setVerified(true);
        $user->setPassword($this->hasher->hashPassword($user, $password ?? $this->demoPassword));

        $this->em->persist($user);

        return 1;
    }
}
