<?php

namespace App\Repository;

use App\Entity\RewatchWinner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method RewatchWinner|null find($id, $lockMode = null, $lockVersion = null)
 * @method RewatchWinner|null findOneBy(array $criteria, array $orderBy = null)
 * @method RewatchWinner[]    findAll()
 * @method RewatchWinner[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RewatchWinnerRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RewatchWinner::class);
    }

//    /**
//     * @return RewatchWinner[] Returns an array of RewatchWinner objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RewatchWinner
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
