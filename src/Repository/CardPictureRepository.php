<?php

namespace App\Repository;

use App\Entity\CardPicture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CardPicture>
 *
 * @method CardPicture|null find($id, $lockMode = null, $lockVersion = null)
 * @method CardPicture|null findOneBy(array $criteria, array $orderBy = null)
 * @method CardPicture[]    findAll()
 * @method CardPicture[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CardPictureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CardPicture::class);
    }

    //    /**
    //     * @return CardPicture[] Returns an array of CardPicture objects
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

    //    public function findOneBySomeField($value): ?CardPicture
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
