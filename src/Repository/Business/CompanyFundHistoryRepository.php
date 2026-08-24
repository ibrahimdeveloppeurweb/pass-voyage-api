<?php

namespace App\Repository\Business;

use App\Entity\Business\CompanyFundHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompanyFundHistory>
 */
class CompanyFundHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanyFundHistory::class);
    }

    public function findByCompanyFund($companyFund, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('h')
            ->where('h.companyFund = :fund')
            ->setParameter('fund', $companyFund)
            ->orderBy('h.id', 'DESC');

        if (!empty($filters['type'])) {
            $qb->andWhere('h.type = :type')
                ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . mb_strtolower(trim($filters['search'])) . '%';
            $qb->andWhere('LOWER(h.reference) LIKE :search OR LOWER(h.description) LIKE :search OR LOWER(h.performedBy) LIKE :search')
                ->setParameter('search', $search);
        }

        return $qb->getQuery()->getResult();
    }
}
