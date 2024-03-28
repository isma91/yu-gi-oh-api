<?php

namespace App\Repository;

use App\Entity\CardCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CardCollection>
 *
 * @method CardCollection|null find($id, $lockMode = null, $lockVersion = null)
 * @method CardCollection|null findOneBy(array $criteria, array $orderBy = null)
 * @method CardCollection[]    findAll()
 * @method CardCollection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CardCollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CardCollection::class);
    }

    /**
     * @param QueryBuilder $qb
     * @param array $filter
     * @return QueryBuilder
     */
    public function returnQueryBuilderSearchFilter(QueryBuilder $qb, array $filter): QueryBuilder
    {
        if (isset($filter["slugName"]) === TRUE) {
            $qb->andWhere("cardCollection.slugName LIKE :cardSlugName")
                ->setParameter("cardSlugName", '%' . $filter["slugName"] . '%');
        }
        if (isset($filter["user"]) === TRUE) {
            $qb->andWhere("cardCollection.user = :deckUser")
                ->setParameter("deckUser", $filter["user"]);
        }
        return $qb;
    }

    /**
     * @param array $filter
     * @param int $offset
     * @param int $limit
     * @return CardCollection[]
     */
    public function findFromSearchFilter(array $filter, int $offset, int $limit): array
    {
        $qb = $this->createQueryBuilder("cardCollection");
        $qb = $this->returnQueryBuilderSearchFilter($qb, $filter);
        return $qb->setMaxResults($limit)
            ->setFirstResult($offset * $limit)
            ->orderBy("cardCollection.slugName", "ASC")
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
        $qb = $this->createQueryBuilder("cardCollection")
            ->select("COUNT(cardCollection.id)");
        $qb = $this->returnQueryBuilderSearchFilter($qb, $filter);
        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return CardCollection[] Returns an array of CardCollection objects
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

    //    public function findOneBySomeField($value): ?CardCollection
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
