<?php

namespace App\Repository\Business;

use App\Entity\Business\PassengerContact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PassengerContact>
 *
 * @method PassengerContact|null find($id, $lockMode = null, $lockVersion = null)
 * @method PassengerContact|null findOneBy(array $criteria, array $orderBy = null)
 * @method PassengerContact[]    findAll()
 * @method PassengerContact[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PassengerContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PassengerContact::class);
    }

    public function add(PassengerContact $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PassengerContact $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
