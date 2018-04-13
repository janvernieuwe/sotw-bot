<?php

namespace App\Repository;

use App\Entity\SotwWinner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method SotwWinner|null find($id, $lockMode = null, $lockVersion = null)
 * @method SotwWinner|null findOneBy(array $criteria, array $orderBy = null)
 * @method SotwWinner[]    findAll()
 * @method SotwWinner[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SotwWinnerRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, SotwWinner::class);
    }

//    /**
//     * @return SotwWinner[] Returns an array of SotwWinner objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SotwWinner
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
