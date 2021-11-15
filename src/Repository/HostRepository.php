<?php

namespace App\Repository;

use App\Entity\Host;
use App\Entity\Operation;
use App\Timestamp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use IPLib\Address\AddressInterface;
use IPLib\Address\Type as IpType;

/**
 * @method Host|null find($id, $lockMode = null, $lockVersion = null)
 * @method Host|null findOneBy(array $criteria, array $orderBy = null)
 * @method Host[]    findAll()
 * @method Host[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HostRepository extends ServiceEntityRepository
{
    use DoctrineTypeTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Host::class);
    }

    /**
     * @return Host[]|null
     */
    public function findExpired(): ?array
    {
        return $this->createQueryBuilder('t')
            ->where('t.deletion IS NULL')
            ->andWhere('t.expiresAt <= :now')
            ->setParameter('now', Timestamp::now(), $this->getDoctrineType('expiresAt'))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Host[]|null
     */
    public function findToDispatchDeletion(): ?array
    {
        return $this->createQueryBuilder('t')
            ->join('t.deletion', 'd')
            ->where('d.dispatchedAt IS NULL')
            ->andWhere('d.handledAt IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function findByIpAddress(AddressInterface $ip): ?Host
    {
        if (IpType::T_IPv4 !== $ip->getAddressType()) {
            return null;
        }

        return $this->createQueryBuilder('t')
            ->where('t.ipAddress = :ip')
            ->andWhere('t.deletion IS NULL')
            ->setParameter('ip', $ip, $this->getDoctrineType('ipAddress'))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countReach(Host $host) : void
    {
        $this->_em->createQueryBuilder()
            ->update($this->getEntityName(), 't')
            ->set('t.reachesToExpire', 't.reachesToExpire - 1')
            ->where('t.id = :id')
            ->andWhere('t.reachesToExpire IS NOT NULL')
            ->setParameter('id', $host->getId(), $this->getDoctrineType('id'))
            ->getQuery()
            ->execute()
        ;
    }

    public function deleteHost(Host $host) : void
    {
        $this->_em->createQueryBuilder()
            ->update(Operation::class, 't')
            ->set('t.host', 'NULL')
            ->where('IDENTITY(t.host) = :id')
            ->setParameter('id', $host->getId(), $this->getDoctrineType('id'))
            ->getQuery()
            ->execute()
        ;
        $this->_em->remove($host);
        $this->_em->flush();
    }
}
