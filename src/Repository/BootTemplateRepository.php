<?php

namespace App\Repository;

use App\Entity\BootTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BootTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method BootTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method BootTemplate[]    findAll()
 * @method BootTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BootTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BootTemplate::class);
    }

    // /**
    //  * @return BootTemplate[] Returns an array of BootTemplate objects
    //  */
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
    public function findOneBySomeField($value): ?BootTemplate
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
