<?php

namespace App\Repository\Business;

use App\Entity\Business\CreditPolicy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CreditPolicy>
 *
 * @method CreditPolicy|null find($id, $lockMode = null, $lockVersion = null)
 * @method CreditPolicy|null findOneBy(array $criteria, array $orderBy = null)
 * @method CreditPolicy[]    findAll()
 * @method CreditPolicy[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CreditPolicyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreditPolicy::class);
    }

    public function add(CreditPolicy $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CreditPolicy $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
