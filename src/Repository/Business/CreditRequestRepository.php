<?php

namespace App\Repository\Business;

use App\Entity\Business\CreditRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CreditRequest>
 *
 * @method CreditRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method CreditRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method CreditRequest[]    findAll()
 * @method CreditRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CreditRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreditRequest::class);
    }

    public function add(CreditRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CreditRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
