<?php

namespace App\Repository;

use App\Entity\Host;
use App\Entity\Operation;
use App\Timestamp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @method Operation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Operation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Operation[]    findAll()
 * @method Operation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OperationRepository extends ServiceEntityRepository
{
    use DoctrineTypeTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Operation::class);
    }

    /** @return Operation[]|null */
    public function findUnhandled(): ?array
    {
        return $this->createQueryBuilder('t')
            ->where('t.handledAt IS NULL')
            ->andWhere('t.dispatchedAt < :before')
            ->setParameter('before', Timestamp::now()->after(-10 * 60), $this->getDoctrineType('dispatchedAt'))
            ->getQuery()
            ->getResult();
    }

    public function gc() : void
    {
        $this->_em->createQueryBuilder()
            ->delete(Operation::class, 't')
            ->where('t.handledAt IS NOT NULL')
            ->getQuery()
            ->execute()
        ;
    }
}
