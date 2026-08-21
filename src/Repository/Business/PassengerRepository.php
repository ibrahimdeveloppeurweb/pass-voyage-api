<?php

namespace App\Repository\Business;

use App\Entity\Business\Passenger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Passenger>
 *
 * @method Passenger|null find($id, $lockMode = null, $lockVersion = null)
 * @method Passenger|null findOneBy(array $criteria, array $orderBy = null)
 * @method Passenger[]    findAll()
 * @method Passenger[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PassengerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Passenger::class);
    }

    public function add(Passenger $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Passenger $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByFilters(array $filters, int $delaiDays = 14): array
    {
        $qb = $this->createQueryBuilder('p');

        if (!empty($filters['search'])) {
            $rawTerm = trim($filters['search']);
            $search = '%' . mb_strtolower($rawTerm) . '%';
            $qb->andWhere('LOWER(p.firstname) LIKE :search OR LOWER(p.lastname) LIKE :search OR LOWER(CONCAT(p.firstname, \' \', p.lastname)) LIKE :search OR LOWER(p.phoneNumber) LIKE :search OR LOWER(p.email) LIKE :search OR LOWER(p.uuid) LIKE :search OR LOWER(p.code) LIKE :search')
                ->setParameter('search', $search);

            if (is_numeric($rawTerm)) {
                $qb->orWhere('p.id = :searchId')
                    ->setParameter('searchId', (int)$rawTerm);
            }
        }

        if (!empty($filters['status'])) {
            $st = $filters['status'];
            if (in_array($st, ['VERIFIED', 'Vérifié', 'Dossier Approuvé'])) {
                $qb->andWhere('UPPER(p.identityStatus) IN (:verifiedStatuses) OR p.isIdentified = true')
                    ->setParameter('verifiedStatuses', ['VERIFIED', 'VALIDATED', 'APPROVED']);
            } elseif (in_array($st, ['PENDING', 'En attente de Validation', 'En Attente Validation'])) {
                $qb->andWhere('UPPER(p.identityStatus) = :pendingStatus')
                    ->setParameter('pendingStatus', 'PENDING');
            } elseif (in_array($st, ['REJECTED', 'Rejeté'])) {
                $qb->andWhere('UPPER(p.identityStatus) = :rejectedStatus')
                    ->setParameter('rejectedStatus', 'REJECTED');
            } elseif (in_array($st, ['CREATED', 'Compte Créé', 'Prospect', 'Inactif'])) {
                $qb->andWhere('(p.identityStatus IS NULL OR UPPER(p.identityStatus) NOT IN (:nonCreatedStatuses)) AND (p.isIdentified IS NULL OR p.isIdentified = false)')
                    ->setParameter('nonCreatedStatuses', ['VERIFIED', 'VALIDATED', 'APPROVED', 'PENDING']);
            } elseif (in_array($st, ['BLACKLISTED', 'BLOQUE', 'Bloqué', 'BLACK_LISTED'])) {
                $qb->andWhere('p.isBlacklisted = true');
            }
        }

        if (!empty($filters['financialStatus'])) {
            $fStatus = $filters['financialStatus'];
            if (in_array($fStatus, ['À jour / Soldé', 'A_JOUR', 'Sans Solde', 'A_JOUR_SOLDE'])) {
                $qb->andWhere('p.totalDebt IS NULL OR p.totalDebt = 0');
            } elseif (in_array($fStatus, ['Impayé', 'IMPAYE', 'Avec Solde', 'Impayé (Retards)'])) {
                $qb->andWhere('p.totalDebt > 0');
            }
        }

        if (array_key_exists('minDebt', $filters) && $filters['minDebt'] !== null && $filters['minDebt'] !== '' && $filters['minDebt'] !== 'null') {
            $qb->andWhere('p.totalDebt >= :minDebt')
                ->setParameter('minDebt', (int) $filters['minDebt']);
        }

        if (array_key_exists('maxDebt', $filters) && $filters['maxDebt'] !== null && $filters['maxDebt'] !== '' && $filters['maxDebt'] !== 'null') {
            $qb->andWhere('p.totalDebt <= :maxDebt')
                ->setParameter('maxDebt', (int) $filters['maxDebt']);
        }

        $qb->orderBy('p.id', 'DESC');

        if (!empty($filters['count']) && is_numeric($filters['count'])) {
            $limit = (int) $filters['count'];
            if ($limit > 0 && $limit < 10000) {
                $qb->setMaxResults($limit);
            }
        } else {
            $qb->setMaxResults(20);
        }

        $results = $qb->getQuery()->getResult();

        // If filtering specifically by overdue status
        if (
            (!empty($filters['financialStatus']) && in_array($filters['financialStatus'], ['Impayé (Retards)', 'EN_RETARD', 'RETARD', 'OVERDUE', 'IMPAYE_RETARD'])) ||
            !empty($filters['onlyOverdue']) ||
            !empty($filters['isOverdue'])
        ) {
            $results = array_filter($results, function(Passenger $p) use ($delaiDays) {
                return $p->getDaysOverdue($delaiDays) > 0;
            });
        }

        return array_values($results);
    }
}
