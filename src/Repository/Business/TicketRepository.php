<?php

namespace App\Repository\Business;

use App\Entity\Business\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 *
 * @method Ticket|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ticket|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ticket[]    findAll()
 * @method Ticket[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function add(Ticket $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Ticket $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.creditRequest', 'c')
            ->leftJoin('c.passenger', 'p')
            ->leftJoin('t.company', 'comp')
            ->leftJoin('c.company', 'crComp');

        if (!empty($filters['search'])) {
            $rawTerm = trim($filters['search']);
            $search = '%' . mb_strtolower($rawTerm) . '%';

            $qb->andWhere('LOWER(t.ticketNumber) LIKE :search OR LOWER(p.firstname) LIKE :search OR LOWER(p.lastname) LIKE :search OR LOWER(CONCAT(p.firstname, \' \', p.lastname)) LIKE :search OR LOWER(p.phoneNumber) LIKE :search OR LOWER(comp.name) LIKE :search OR LOWER(crComp.name) LIKE :search OR LOWER(c.departureCity) LIKE :search OR LOWER(c.arrivalCity) LIKE :search OR LOWER(CONCAT(c.departureCity, \' - \', c.arrivalCity)) LIKE :search')
                ->setParameter('search', $search);

            if (is_numeric($rawTerm)) {
                $qb->orWhere('t.id = :searchId')
                    ->setParameter('searchId', (int)$rawTerm);
            }
        }

        if (!empty($filters['status'])) {
            $st = mb_strtolower(trim($filters['status']));
            if (in_array($st, ['scanné', 'scanne', 'scanned', 'used', 'consommé', 'consomme'])) {
                $qb->andWhere('t.isUsed = true OR UPPER(t.status) IN (:scannedStatuses)')
                    ->setParameter('scannedStatuses', ['USED', 'SCANNED', 'SCANNE', 'CONSOMME']);
            } elseif (in_array($st, ['expiré', 'expire', 'expired'])) {
                $qb->andWhere('UPPER(t.status) IN (:expiredStatuses)')
                    ->setParameter('expiredStatuses', ['EXPIRED', 'EXPIRE']);
            } elseif (in_array($st, ['annulé', 'annule', 'cancelled', 'refused', 'rejected'])) {
                $qb->andWhere('UPPER(t.status) IN (:cancelledStatuses)')
                    ->setParameter('cancelledStatuses', ['CANCELLED', 'REFUSED', 'REJECTED']);
            } elseif (in_array($st, ['valide', 'valid'])) {
                $qb->andWhere('(t.isUsed IS NULL OR t.isUsed = false) AND (t.status IS NULL OR UPPER(t.status) NOT IN (:nonValideStatuses))')
                    ->setParameter('nonValideStatuses', ['USED', 'SCANNED', 'SCANNE', 'CONSOMME', 'EXPIRED', 'EXPIRE', 'CANCELLED', 'REFUSED', 'REJECTED']);
            }
        }

        if (!empty($filters['company'])) {
            $compSearch = '%' . mb_strtolower(trim($filters['company'])) . '%';
            $qb->andWhere('LOWER(comp.name) LIKE :compSearch OR LOWER(crComp.name) LIKE :compSearch')
                ->setParameter('compSearch', $compSearch);
        }

        $qb->orderBy('t.id', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
