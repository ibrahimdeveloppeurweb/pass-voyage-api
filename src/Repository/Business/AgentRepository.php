<?php

namespace App\Repository\Business;

use App\Entity\Business\Agent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Agent>
 *
 * @method Agent|null find($id, $lockMode = null, $lockVersion = null)
 * @method Agent|null findOneBy(array $criteria, array $orderBy = null)
 * @method Agent[]    findAll()
 * @method Agent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AgentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Agent::class);
    }

    public function add(Agent $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Agent $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAgentPerformances(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.company', 'comp')
            ->leftJoin('a.stationAssigned', 'st');

        if (!empty($filters['search'])) {
            $search = '%' . mb_strtolower(trim($filters['search'])) . '%';
            $qb->andWhere('LOWER(a.firstname) LIKE :search OR LOWER(a.lastname) LIKE :search OR LOWER(CONCAT(a.firstname, \' \', a.lastname)) LIKE :search OR LOWER(CONCAT(a.lastname, \' \', a.firstname)) LIKE :search OR LOWER(a.agentCode) LIKE :search OR LOWER(a.phoneNumber) LIKE :search OR LOWER(comp.name) LIKE :search OR LOWER(st.name) LIKE :search')
                ->setParameter('search', $search);
        }

        $qb->orderBy('a.lastname', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
