<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\HttpFoundation\Request;

class DataTableService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Process DataTables request and return formatted response
     */
    public function getData(Request $request, ServiceEntityRepository $repository): string
    {
        // Récupérer les paramètres DataTables
        $draw = intval($request->get('draw', 1));
        $start = intval($request->get('start', 0));
        $length = intval($request->get('length', 10));
        $search = $request->get('search', []);
        $order = $request->get('order', []);
        $columns = $request->get('columns', []);

        try {
            // Construire la requête de base
            $entityClass = $repository->getClassName();
            $alias = $this->getEntityAlias($entityClass);
            $qb = $repository->createQueryBuilder($alias);

            // Appliquer la recherche globale
            if (!empty($search['value'])) {
                $this->applyGlobalSearch($qb, $alias, $search['value'], $columns);
            }

            // Appliquer la recherche par colonne
            $this->applyColumnSearch($qb, $alias, $columns);

            // Appliquer le tri
            $this->applyOrdering($qb, $alias, $order, $columns);

            // Compter le total filtré
            $totalFiltered = $this->countResults($qb);

            // Appliquer la pagination
            $qb->setFirstResult($start)->setMaxResults($length);

            // Exécuter la requête
            $results = $qb->getQuery()->getResult();

            // Compter le total sans filtre
            $totalRecords = $this->countTotal($repository, $alias);

            // Formater les données
            $data = $this->formatData($results);

            // Construire la réponse
            $response = [
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalFiltered,
                'data' => $data
            ];

            return json_encode($response);

        } catch (\Exception $e) {
            return json_encode([
                'error' => 'Erreur lors du traitement des données: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Apply global search to query builder
     */
    private function applyGlobalSearch(QueryBuilder $qb, string $alias, string $searchValue, array $columns): void
    {
        $searchableColumns = [];
        foreach ($columns as $column) {
            if (isset($column['searchable']) && $column['searchable'] === 'true' && !empty($column['data'])) {
                $searchableColumns[] = $column['data'];
            }
        }

        if (!empty($searchableColumns)) {
            $orConditions = [];
            foreach ($searchableColumns as $column) {
                $orConditions[] = $qb->expr()->like("$alias.$column", ':globalSearch');
            }

            if (!empty($orConditions)) {
                $qb->andWhere($qb->expr()->orX(...$orConditions))
                   ->setParameter('globalSearch', '%' . $searchValue . '%');
            }
        }
    }

    /**
     * Apply column-specific search to query builder
     */
    private function applyColumnSearch(QueryBuilder $qb, string $alias, array $columns): void
    {
        foreach ($columns as $index => $column) {
            if (isset($column['search']['value']) && 
                !empty($column['search']['value']) && 
                isset($column['searchable']) && 
                $column['searchable'] === 'true' &&
                !empty($column['data'])) {
                
                $paramName = 'search_' . $index;
                $qb->andWhere($qb->expr()->like("$alias.{$column['data']}", ":$paramName"))
                   ->setParameter($paramName, '%' . $column['search']['value'] . '%');
            }
        }
    }

    /**
     * Apply ordering to query builder
     */
    private function applyOrdering(QueryBuilder $qb, string $alias, array $order, array $columns): void
    {
        foreach ($order as $orderItem) {
            $columnIndex = intval($orderItem['column']);
            $direction = strtoupper($orderItem['dir']) === 'DESC' ? 'DESC' : 'ASC';

            if (isset($columns[$columnIndex]) && 
                isset($columns[$columnIndex]['orderable']) && 
                $columns[$columnIndex]['orderable'] === 'true' &&
                !empty($columns[$columnIndex]['data'])) {
                
                $columnName = $columns[$columnIndex]['data'];
                $qb->addOrderBy("$alias.$columnName", $direction);
            }
        }

        // Ordre par défaut si aucun tri n'est spécifié
        if (empty($order)) {
            $qb->orderBy("$alias.id", 'DESC');
        }
    }

    /**
     * Count total results for current query
     */
    private function countResults(QueryBuilder $qb): int
    {
        $countQb = clone $qb;
        $countQb->select('COUNT(' . $countQb->getRootAliases()[0] . '.id)')
                ->resetDQLPart('orderBy')
                ->setFirstResult(0)
                ->setMaxResults(null);

        return intval($countQb->getQuery()->getSingleScalarResult());
    }

    /**
     * Count total records without filters
     */
    private function countTotal(ServiceEntityRepository $repository, string $alias): int
    {
        return intval(
            $repository->createQueryBuilder($alias)
                      ->select("COUNT($alias.id)")
                      ->getQuery()
                      ->getSingleScalarResult()
        );
    }

    /**
     * Format data for DataTables response
     */
    private function formatData(array $results): array
    {
        $data = [];
        foreach ($results as $entity) {
            $data[] = $this->entityToArray($entity);
        }
        return $data;
    }

    /**
     * Convert entity to array
     */
    private function entityToArray($entity): array
    {
        $reflection = new \ReflectionClass($entity);
        $data = [];

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($entity);

            // Conversion des types spéciaux
            if ($value instanceof \DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            } elseif (is_bool($value)) {
                $value = $value ? 1 : 0;
            } elseif (is_object($value)) {
                $value = method_exists($value, '__toString') ? (string) $value : null;
            }

            $data[$property->getName()] = $value;
        }

        return $data;
    }

    /**
     * Get entity alias from class name
     */
    private function getEntityAlias(string $entityClass): string
    {
        $parts = explode('\\', $entityClass);
        $className = end($parts);
        return strtolower(substr($className, 0, 1));
    }

    /**
     * Advanced DataTables processing with custom callback
     */
    public function getDataWithCallback(
        Request $request, 
        ServiceEntityRepository $repository, 
        callable $dataFormatter = null,
        array $searchableFields = [],
        array $orderableFields = []
    ): string {
        $draw = intval($request->get('draw', 1));
        $start = intval($request->get('start', 0));
        $length = intval($request->get('length', 10));
        $search = $request->get('search', []);
        $order = $request->get('order', []);
        $columns = $request->get('columns', []);

        try {
            $entityClass = $repository->getClassName();
            $alias = $this->getEntityAlias($entityClass);
            $qb = $repository->createQueryBuilder($alias);

            // Recherche personnalisée
            if (!empty($search['value']) && !empty($searchableFields)) {
                $this->applyCustomGlobalSearch($qb, $alias, $search['value'], $searchableFields);
            }

            // Tri personnalisé
            if (!empty($order) && !empty($orderableFields)) {
                $this->applyCustomOrdering($qb, $alias, $order, $orderableFields);
            }

            // Compter le total filtré
            $totalFiltered = $this->countResults($qb);

            // Pagination
            $qb->setFirstResult($start)->setMaxResults($length);
            $results = $qb->getQuery()->getResult();

            // Total sans filtre
            $totalRecords = $this->countTotal($repository, $alias);

            // Formatage des données
            $data = [];
            foreach ($results as $entity) {
                if ($dataFormatter && is_callable($dataFormatter)) {
                    $data[] = $dataFormatter($entity);
                } else {
                    $data[] = $this->entityToArray($entity);
                }
            }

            return json_encode([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalFiltered,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return json_encode([
                'error' => 'Erreur lors du traitement: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Apply custom global search
     */
    private function applyCustomGlobalSearch(QueryBuilder $qb, string $alias, string $searchValue, array $searchableFields): void
    {
        $orConditions = [];
        foreach ($searchableFields as $field) {
            $orConditions[] = $qb->expr()->like("$alias.$field", ':globalSearch');
        }

        if (!empty($orConditions)) {
            $qb->andWhere($qb->expr()->orX(...$orConditions))
               ->setParameter('globalSearch', '%' . $searchValue . '%');
        }
    }

    /**
     * Apply custom ordering
     */
    private function applyCustomOrdering(QueryBuilder $qb, string $alias, array $order, array $orderableFields): void
    {
        foreach ($order as $orderItem) {
            $columnIndex = intval($orderItem['column']);
            $direction = strtoupper($orderItem['dir']) === 'DESC' ? 'DESC' : 'ASC';

            if (isset($orderableFields[$columnIndex])) {
                $field = $orderableFields[$columnIndex];
                $qb->addOrderBy("$alias.$field", $direction);
            }
        }
    }

    /**
     * Get paginated results with search and ordering
     */
    public function getPaginatedResults(
        ServiceEntityRepository $repository,
        int $page = 1,
        int $limit = 10,
        string $search = '',
        array $searchFields = [],
        string $orderBy = 'id',
        string $orderDir = 'DESC'
    ): array {
        $alias = $this->getEntityAlias($repository->getClassName());
        $qb = $repository->createQueryBuilder($alias);

        // Recherche
        if (!empty($search) && !empty($searchFields)) {
            $this->applyCustomGlobalSearch($qb, $alias, $search, $searchFields);
        }

        // Tri
        $qb->orderBy("$alias.$orderBy", strtoupper($orderDir));

        // Pagination
        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();
        $total = $this->countResults($qb);

        return [
            'data' => $results,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
}