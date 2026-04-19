<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates a new admin user',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('cin', InputArgument::REQUIRED, 'The CIN (username)')
            ->addArgument('password', InputArgument::REQUIRED, 'The plain password')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cin = $input->getArgument('cin');
        $password = $input->getArgument('password');

        $user = new User();
        $user->setCin($cin);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $password)
        );

        // Optional: clear any existing user with same cin to prevent UniqueConstraintViolation
        $existing = $this->entityManager->getRepository(User::class)->findOneBy(['cin' => $cin]);
        if ($existing) {
            $existing->setRoles(['ROLE_ADMIN']);
            $existing->setPassword($this->passwordHasher->hashPassword($existing, $password));
            $io->note('User already exists, updated password and roles.');
        } else {
            $this->entityManager->persist($user);
            $io->note('Created new user.');
        }

        $this->entityManager->flush();

        $io->success(sprintf('Admin user %s created/updated successfully.', $cin));

        return Command::SUCCESS;
    }
}
