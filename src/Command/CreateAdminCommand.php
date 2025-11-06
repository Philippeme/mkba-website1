<?php

namespace App\Command;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Créer un utilisateur admin avec les rôles appropriés',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Créer les rôles par défaut
            $this->createDefaultRoles($io);
            
            // Créer l'utilisateur admin
            $this->createAdminUser($io);

            $this->entityManager->flush();

            $io->success('Utilisateur admin créé avec succès !');
            $io->info('Nom d\'utilisateur: admin');
            $io->info('Mot de passe: admin123');
            $io->info('Email: admin@mkba.cm');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de la création de l\'admin: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createDefaultRoles(SymfonyStyle $io): void
    {
        $defaultRoles = [
            [
                'name' => 'ROLE_SUPER_ADMIN',
                'displayName' => 'Super Administrateur',
                'description' => 'Accès complet au système',
                'isSystem' => true,
                'displayOrder' => 1
            ],
            [
                'name' => 'ROLE_ADMIN',
                'displayName' => 'Administrateur',
                'description' => 'Accès administratif général',
                'isSystem' => true,
                'displayOrder' => 2
            ],
            [
                'name' => 'ROLE_MANAGER',
                'displayName' => 'Gestionnaire',
                'description' => 'Gestion des contenus et utilisateurs',
                'isSystem' => false,
                'displayOrder' => 3
            ],
            [
                'name' => 'ROLE_USER',
                'displayName' => 'Utilisateur',
                'description' => 'Utilisateur standard',
                'isSystem' => true,
                'displayOrder' => 4
            ]
        ];

        foreach ($defaultRoles as $roleData) {
            $existingRole = $this->entityManager->getRepository(Role::class)
                ->findOneBy(['name' => $roleData['name']]);

            if (!$existingRole) {
                $role = new Role();
                $role->setName($roleData['name']);
                $role->setDisplayName($roleData['displayName']);
                $role->setDescription($roleData['description']);
                $role->setIsSystem($roleData['isSystem']);
                $role->setIsActive(true);
                $role->setDisplayOrder($roleData['displayOrder']);

                $this->entityManager->persist($role);
                $io->info('Rôle créé: ' . $roleData['displayName']);
            }
        }
    }

    private function createAdminUser(SymfonyStyle $io): void
    {
        // Vérifier si admin existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['username' => 'admin']);

        if ($existingUser) {
            $io->warning('L\'utilisateur admin existe déjà. Mise à jour...');
            $user = $existingUser;
        } else {
            $io->info('Création d\'un nouvel utilisateur admin...');
            $user = new User();
            $user->setUsername('admin');
            $user->setDisplayOrder(1);
            $this->entityManager->persist($user);
        }

        // Configurer l'utilisateur
        $user->setEmail('admin@mkba.cm');
        $user->setFirstName('System');
        $user->setLastName('Administrator');
        $user->setIsActive(true);
        $user->setIsVerified(true);

        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'admin123');
        $user->setPassword($hashedPassword);

        // Assigner le rôle SUPER_ADMIN
        $superAdminRole = $this->entityManager->getRepository(Role::class)
            ->findOneBy(['name' => 'ROLE_SUPER_ADMIN']);

        if ($superAdminRole) {
            $user->addRole($superAdminRole);
        }
    }
}