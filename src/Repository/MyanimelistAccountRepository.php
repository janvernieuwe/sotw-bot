<?php

namespace App\Repository;

use App\Entity\MyanimelistAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MyanimelistAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method MyanimelistAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method MyanimelistAccount[]    findAll()
 * @method MyanimelistAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MyanimelistAccountRepository extends ServiceEntityRepository
{
    /**
     * MyanimelistAccountRepository constructor.
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MyanimelistAccount::class);
    }

    /**
     * @param int $discordId
     * @return MyanimelistAccount|null
     */
    public function findOneByDiscordId(int $discordId): ?MyanimelistAccount
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.discordId = :val')
            ->setParameter('val', $discordId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
