<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:ensure-demo-users',
    description: 'Crée les comptes admin/commercial s’ils n’existent pas encore',
)]
final class EnsureDemoUsersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $repo = $this->em->getRepository(User::class);

        $accounts = [
            ['Admin ServiceLab', 'admin@servicelab.ca', User::ROLE_ADMIN, 'Admin123!'],
            ['Commercial ServiceLab', 'commercial@servicelab.ca', User::ROLE_COMMERCIAL, 'Commercial123!'],
        ];

        foreach ($accounts as [$nom, $email, $role, $plainPassword]) {
            if ($repo->findOneBy(['email' => $email])) {
                $io->note(sprintf('Déjà présent : %s', $email));
                continue;
            }

            $user = (new User())
                ->setNom($nom)
                ->setEmail($email)
                ->setRoles([$role]);
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            $this->em->persist($user);
            $io->success(sprintf('Créé : %s / %s', $email, $plainPassword));
        }

        $this->em->flush();

        return Command::SUCCESS;
    }
}
