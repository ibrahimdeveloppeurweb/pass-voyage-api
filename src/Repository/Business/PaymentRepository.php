<?php

namespace App\Repository\Business;

use App\Entity\Business\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 *
 * @method Payment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payment[]    findAll()
 * @method Payment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function add(Payment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Payment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.passenger', 'pass')
            ->leftJoin('p.creditRequest', 'c');

        if (!empty($filters['search'])) {
            $rawTerm = trim($filters['search']);
            $search = '%' . mb_strtolower($rawTerm) . '%';

            $qb->andWhere('LOWER(p.transactionId) LIKE :search OR LOWER(p.paymentMethod) LIKE :search OR LOWER(p.reference) LIKE :search OR LOWER(pass.firstname) LIKE :search OR LOWER(pass.lastname) LIKE :search OR LOWER(CONCAT(pass.firstname, \' \', pass.lastname)) LIKE :search OR LOWER(pass.phoneNumber) LIKE :search OR LOWER(c.code) LIKE :search')
                ->setParameter('search', $search);

            if (is_numeric($rawTerm)) {
                $qb->orWhere('p.id = :searchId')
                    ->setParameter('searchId', (int)$rawTerm);
            }
        }

        if (!empty($filters['paymentMethod'])) {
            $pm = '%' . mb_strtolower(trim($filters['paymentMethod'])) . '%';
            $qb->andWhere('LOWER(p.paymentMethod) LIKE :pm')
                ->setParameter('pm', $pm);
        }

        if (!empty($filters['type'])) {
            $tp = trim($filters['type']);
            if (strcasecmp($tp, 'frais') === 0 || strcasecmp($tp, 'service') === 0 || strcasecmp($tp, 'Frais de Service') === 0) {
                $qb->andWhere('LOWER(p.transactionId) LIKE :feePrefix')
                    ->setParameter('feePrefix', 'tx-fee-%');
            } elseif (strcasecmp($tp, 'remboursement') === 0 || strcasecmp($tp, 'Remboursement Crédit') === 0) {
                $qb->andWhere('LOWER(p.transactionId) NOT LIKE :feePrefix OR p.transactionId IS NULL')
                    ->setParameter('feePrefix', 'tx-fee-%');
            }
        }

        $qb->orderBy('p.createdAt', 'DESC')
           ->addOrderBy('p.id', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
