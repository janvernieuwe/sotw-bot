<?php

namespace App\Repository;

use App\Entity\Bikkel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Bikkel|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bikkel|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bikkel[]    findAll()
 * @method Bikkel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BikkelRepository extends ServiceEntityRepository
{
    /**
     * BikkelRepository constructor.
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Bikkel::class);
    }

//    /**
//     * @return Bikkel[] Returns an array of Bikkel objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Bikkel
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
