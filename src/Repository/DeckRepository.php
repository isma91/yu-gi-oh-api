<?php

namespace App\Repository;

use App\Entity\Deck;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deck>
 *
 * @method Deck|null find($id, $lockMode = null, $lockVersion = null)
 * @method Deck|null findOneBy(array $criteria, array $orderBy = null)
 * @method Deck[]    findAll()
 * @method Deck[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeckRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deck::class);
    }

    /**
     * @param QueryBuilder $qb
     * @param array $filter
     * @return QueryBuilder
     */
    public function returnQueryBuilderSearchFilter(QueryBuilder $qb, array $filter): QueryBuilder
    {
        if (isset($filter["slugName"]) === TRUE) {
            $qb->andWhere("deck.slugName LIKE :cardSlugName")
                ->setParameter("cardSlugName", '%' . $filter["slugName"] . '%');
        }
        if (isset($filter["user"]) === TRUE) {
            $qb->andWhere("deck.user = :deckUser")
                ->setParameter("deckUser", $filter["user"]);
        }
        return $qb;
    }

    /**
     * @param array $filter
     * @param int $offset
     * @param int $limit
     * @return Deck[]
     */
    public function findFromSearchFilter(array $filter, int $offset, int $limit): array
    {
        $qb = $this->createQueryBuilder("deck");
        $qb = $this->returnQueryBuilderSearchFilter($qb, $filter);
        return $qb->setMaxResults($limit)
            ->setFirstResult($offset * $limit)
            ->orderBy("deck.slugName", "ASC")
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
        $qb = $this->createQueryBuilder("deck")
            ->select("COUNT(deck.id)");
        $qb = $this->returnQueryBuilderSearchFilter($qb, $filter);
        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return Deck[] Returns an array of Deck objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Deck
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
