<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

#[AsCommand(
    name: 'app:user-create',
    description: 'Creates new user',
)]
class UserCreateCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email')
            ->addArgument('username', InputArgument::REQUIRED, 'Username')
            ->addArgument('password', InputArgument::REQUIRED, 'Password')
            ->addArgument('role', InputArgument::OPTIONAL, 'Role', 'ROLE_USER')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $username = $input->getArgument('username');
        $plainPassword = $input->getArgument('password');
        $role = $input->getArgument('role');

        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setRoles([$role]);
        $user->setPlainPassword($plainPassword);
        $user->makeEncodedPassword($this->passwordHasherFactory);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $message = 'User created. Id: '.$user->getId();
        $this->logger->warning($this->getName().': '.$message);
        $io->success($message);

        return Command::SUCCESS;
    }
}
