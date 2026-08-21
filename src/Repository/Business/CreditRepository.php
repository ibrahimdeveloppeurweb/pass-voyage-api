<?php

namespace App\Repository\Business;

use App\Entity\Business\Credit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Credit>
 *
 * @method Credit|null find($id, $lockMode = null, $lockVersion = null)
 * @method Credit|null findOneBy(array $criteria, array $orderBy = null)
 * @method Credit[]    findAll()
 * @method Credit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CreditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Credit::class);
    }

    public function add(Credit $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Credit $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.passenger', 'p')
            ->leftJoin('c.company', 'comp');

        if (!empty($filters['search'])) {
            $rawTerm = trim($filters['search']);
            $search = '%' . mb_strtolower($rawTerm) . '%';

            $qb->andWhere('LOWER(c.code) LIKE :search OR LOWER(c.uuid) LIKE :search OR LOWER(p.firstname) LIKE :search OR LOWER(p.lastname) LIKE :search OR LOWER(CONCAT(p.firstname, \' \', p.lastname)) LIKE :search OR LOWER(p.phoneNumber) LIKE :search OR LOWER(comp.name) LIKE :search OR LOWER(c.departureCity) LIKE :search OR LOWER(c.arrivalCity) LIKE :search OR LOWER(CONCAT(c.departureCity, \' - \', c.arrivalCity)) LIKE :search')
                ->setParameter('search', $search);

            if (is_numeric($rawTerm)) {
                $qb->orWhere('c.id = :searchId')
                    ->setParameter('searchId', (int)$rawTerm);
            }
        }

        if (!empty($filters['status'])) {
            $st = mb_strtolower(trim($filters['status']));
            if (in_array($st, ['en attente', 'en_attente', 'pending', 'submitted', 'in_progress'])) {
                $qb->andWhere('UPPER(c.status) IN (:pendingStatuses)')
                    ->setParameter('pendingStatuses', ['PENDING', 'PENDING_VALIDATION', 'EN_ATTENTE', 'SUBMITTED', 'IN_PROGRESS']);
            } elseif (in_array($st, ['approuvé', 'approuve', 'approved', 'valide', 'validated', 'paid', 'repaid'])) {
                $qb->andWhere('UPPER(c.status) IN (:approvedStatuses)')
                    ->setParameter('approvedStatuses', ['APPROVED', 'VALIDE', 'VALIDATED', 'PAID', 'REPAID']);
            } elseif (in_array($st, ['rejeté', 'rejete', 'rejected', 'refuse', 'cancelled'])) {
                $qb->andWhere('UPPER(c.status) IN (:rejectedStatuses)')
                    ->setParameter('rejectedStatuses', ['REJECTED', 'REFUSE', 'CANCELLED']);
            }
        }

        if (!empty($filters['company'])) {
            $compSearch = '%' . mb_strtolower(trim($filters['company'])) . '%';
            $qb->andWhere('LOWER(comp.name) LIKE :compSearch')
                ->setParameter('compSearch', $compSearch);
        }

        $qb->orderBy('c.createdAt', 'DESC')
           ->addOrderBy('c.id', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
