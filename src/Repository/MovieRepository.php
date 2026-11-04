<?php

namespace App\Repository;

use App\Entity\Movie;
use App\Model\MovieFilterDTO;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Movie>
 */
class MovieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Movie::class);
    }

    public function createFilteredQueryBuilder(MovieFilterDTO $filters): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('m')
            ->orderBy('m.id', 'ASC');

        if (null !== $filters->title && '' !== $filters->title) {
            $queryBuilder
                ->andWhere('LOWER(m.title) LIKE :title')
                ->setParameter('title', '%'.mb_strtolower($filters->title).'%');
        }

        if (null !== $filters->year) {
            $queryBuilder
                ->andWhere('m.year = :year')
                ->setParameter('year', $filters->year);
        }

        return $queryBuilder;
    }
}
