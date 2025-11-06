<?php

namespace App\Repository;

use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * Find active documents
     */
    public function findActiveDocuments(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('d.displayOrder', 'ASC')
            ->addOrderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * DataTables server-side processing query
     */
    public function getDataTablesQuery(array $search = [], array $order = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('d');

        // Global search
        if (!empty($search['value'])) {
            $searchValue = '%' . $search['value'] . '%';
            $qb->andWhere('d.nom LIKE :search OR d.description LIKE :search OR d.originalFilename LIKE :search')
               ->setParameter('search', $searchValue);
        }

        // Column-specific search
        if (!empty($search['columns'])) {
            foreach ($search['columns'] as $columnIndex => $column) {
                if (!empty($column['search']['value'])) {
                    $columnSearch = '%' . $column['search']['value'] . '%';
                    switch ($columnIndex) {
                        case 1: // nom
                            $qb->andWhere('d.nom LIKE :nom_search')
                               ->setParameter('nom_search', $columnSearch);
                            break;
                        case 2: // description
                            $qb->andWhere('d.description LIKE :desc_search')
                               ->setParameter('desc_search', $columnSearch);
                            break;
                        case 3: // originalFilename
                            $qb->andWhere('d.originalFilename LIKE :file_search')
                               ->setParameter('file_search', $columnSearch);
                            break;
                    }
                }
            }
        }

        // Ordering
        if (!empty($order)) {
            foreach ($order as $orderItem) {
                $columnIndex = $orderItem['column'];
                $direction = $orderItem['dir'] === 'desc' ? 'DESC' : 'ASC';
                
                switch ($columnIndex) {
                    case 1:
                        $qb->addOrderBy('d.nom', $direction);
                        break;
                    case 2:
                        $qb->addOrderBy('d.description', $direction);
                        break;
                    case 3:
                        $qb->addOrderBy('d.originalFilename', $direction);
                        break;
                    case 4:
                        $qb->addOrderBy('d.fileSize', $direction);
                        break;
                    case 5:
                        $qb->addOrderBy('d.createdAt', $direction);
                        break;
                    case 6:
                        $qb->addOrderBy('d.isActive', $direction);
                        break;
                    default:
                        $qb->addOrderBy('d.id', $direction);
                        break;
                }
            }
        } else {
            // Default ordering
            $qb->orderBy('d.displayOrder', 'ASC')
               ->addOrderBy('d.createdAt', 'DESC');
        }

        return $qb;
    }

    /**
     * Get paginated results for DataTables
     */
    public function getDataTablesResults(array $search = [], array $order = [], int $start = 0, int $length = 10): array
    {
        $qb = $this->getDataTablesQuery($search, $order);
        
        // Apply pagination
        $qb->setFirstResult($start)
           ->setMaxResults($length);

        return $qb->getQuery()->getResult();
    }

    /**
     * Count total records for DataTables
     */
    public function countTotal(): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count filtered records for DataTables
     */
    public function countFiltered(array $search = []): int
    {
        $qb = $this->getDataTablesQuery($search);
        
        return $qb->select('COUNT(d.id)')
                  ->getQuery()
                  ->getSingleScalarResult();
    }

    /**
     * Find documents by extension
     */
    public function findByExtension(string $extension): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.originalFilename LIKE :extension')
            ->andWhere('d.isActive = :active')
            ->setParameter('extension', '%.' . $extension)
            ->setParameter('active', true)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find documents by mime type
     */
    public function findByMimeType(string $mimeType): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.mimeType = :mimeType')
            ->andWhere('d.isActive = :active')
            ->setParameter('mimeType', $mimeType)
            ->setParameter('active', true)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get storage statistics
     */
    public function getStorageStats(): array
    {
        $result = $this->createQueryBuilder('d')
            ->select([
                'COUNT(d.id) as totalDocuments',
                'SUM(d.fileSize) as totalSize',
                'AVG(d.fileSize) as averageSize',
                'MAX(d.fileSize) as maxSize',
                'MIN(d.fileSize) as minSize'
            ])
            ->andWhere('d.isActive = :active')
            ->andWhere('d.fileSize IS NOT NULL')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleResult();

        return [
            'totalDocuments' => (int) $result['totalDocuments'],
            'totalSize' => (int) $result['totalSize'],
            'averageSize' => (float) $result['averageSize'],
            'maxSize' => (int) $result['maxSize'],
            'minSize' => (int) $result['minSize']
        ];
    }

    /**
     * Find recent documents
     */
    public function findRecentDocuments(int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search documents by name or description
     */
    public function searchDocuments(string $query, int $limit = 20): array
    {
        $searchQuery = '%' . $query . '%';
        
        return $this->createQueryBuilder('d')
            ->andWhere('d.nom LIKE :query OR d.description LIKE :query')
            ->andWhere('d.isActive = :active')
            ->setParameter('query', $searchQuery)
            ->setParameter('active', true)
            ->orderBy('d.nom', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}