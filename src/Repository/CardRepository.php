<?php

namespace App\Repository;

use App\Entity\Card;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 *
 * @method Card|null find($id, $lockMode = null, $lockVersion = null)
 * @method Card|null findOneBy(array $criteria, array $orderBy = null)
 * @method Card[]    findAll()
 * @method Card[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    /**
     * @param QueryBuilder $qb
     * @param array $filter
     * @return QueryBuilder
     */
    public function returnQueryBuilderSearchFilter(QueryBuilder $qb, array $filter): QueryBuilder
    {
        if (isset($filter["slugName"]) === TRUE) {
            $qb->andWhere("card.slugName LIKE :card_slugName OR card.slugDescription LIKE :card_slugName")
                ->setParameter("card_slugName", '%' . $filter["slugName"] . '%');
        }
        return $qb;
    }

    /**
     * @param array $filter
     * @param int $offset
     * @param int $limit
     * @return Card[]
     */
    public function findFromSearchFilter(array $filter, int $offset, int $limit): array
    {
        $qb = $this->createQueryBuilder("card");
        $qb = $this->returnQueryBuilderSearchFilter($qb, $filter);
        return $qb->setMaxResults($limit)
            ->setFirstResult($offset * $limit)
            ->orderBy("card.slugName", "ASC")
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
        $qb = $this->createQueryBuilder("card")
            ->select("COUNT(card.id)");
        $qb = $this->returnQueryBuilderSearchFilter($qb, $filter);
        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return Card[] Returns an array of Card objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Card
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
