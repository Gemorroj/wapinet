<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UserCreateCommand extends Command
{
    protected static $defaultName = 'app:user-create';
    private EntityManagerInterface $entityManager;
    private EncoderFactoryInterface $encoderFactory;

    public function __construct(EntityManagerInterface $entityManager, EncoderFactoryInterface $encoderFactory, string $name = null)
    {
        $this->entityManager = $entityManager;
        $this->encoderFactory = $encoderFactory;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Creates new user')
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
        $user->makeEncodedPassword($this->encoderFactory);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('User created. Id: '.$user->getId());

        return 0;
    }
}