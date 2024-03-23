<?php

namespace App\Repository;

use App\Entity\Set;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Set>
 *
 * @method Set|null find($id, $lockMode = null, $lockVersion = null)
 * @method Set|null findOneBy(array $criteria, array $orderBy = null)
 * @method Set[]    findAll()
 * @method Set[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Set::class);
    }

    /**
     * @param QueryBuilder $qb
     * @param array $filter
     * @return QueryBuilder
     */
    public function returnQueryBuilderSearchFilter(QueryBuilder $qb, array $filter): QueryBuilder
    {
        if (isset($filter["slugName"]) === TRUE) {
            $qb->andWhere("s.slugName LIKE :cardSlugName")
                ->setParameter("cardSlugName", '%' . $filter["slugName"] . '%');
        }
        if (isset($filter["code"]) === TRUE) {
            $qb->andWhere("s.code = :code")
                ->setParameter("code", $filter["code"]);
        }
        if (isset($filter["dateBegin"]) === TRUE) {
            $qb->andWhere("s.releaseDate >= :dateBegin")
                ->setParameter("dateBegin", $filter["dateBegin"]);
        }
        if (isset($filter["dateEnd"]) === TRUE) {
            $qb->andWhere("s.releaseDate <= :dateEnd")
                ->setParameter("dateEnd", $filter["dateEnd"]);
        }
        return $qb;
    }

    /**
     * @param array $filter
     * @param int $offset
     * @param int $limit
     * @return Set[]
     */
    public function findFromSearchFilter(array $filter, int $offset, int $limit): array
    {
        $qb = $this->createQueryBuilder("s");
        $qb = $this->returnQueryBuilderSearchFilter($qb, $filter);
        return $qb->setMaxResults($limit)
            ->setFirstResult($offset * $limit)
            ->orderBy("s.slugName", "ASC")
            ->getQuery()->getResult();
    }

    /**
     * @param array $filter
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countFromSearchFilter(array $filter): int
    {
        $qb = $this->createQueryBuilder("s")
            ->select("COUNT(s.id)");
        $qb = $this->returnQueryBuilderSearchFilter($qb, $filter);
        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return Set[] Returns an array of Set objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Set
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
