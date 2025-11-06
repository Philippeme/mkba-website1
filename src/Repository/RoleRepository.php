<?php

namespace App\Repository;

use App\Entity\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Role>
 */
class RoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    /**
     * Find active roles
     */
    public function findActiveRoles(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('r.displayOrder', 'ASC')
            ->addOrderBy('r.displayName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find non-system roles
     */
    public function findNonSystemRoles(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isSystem = :system')
            ->andWhere('r.isActive = :active')
            ->setParameter('system', false)
            ->setParameter('active', true)
            ->orderBy('r.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find role by name
     */
    public function findByName(string $name): ?Role
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Count total roles
     */
    public function countRoles(): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count active roles
     */
    public function countActiveRoles(): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}