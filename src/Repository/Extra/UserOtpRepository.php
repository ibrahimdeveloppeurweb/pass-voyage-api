<?php

namespace App\Repository\Extra;

use App\Entity\Extra\UserOtp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserOtp|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserOtp|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserOtp[]    findAll()
 * @method UserOtp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserOtpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserOtp::class);
    }

    /**
     * Trouve le dernier OTP valide pour un numéro de téléphone
     */
    public function findLatestValidOtp(string $phone, string $code): ?UserOtp
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.phone = :phone')
            ->andWhere('u.code = :code')
            ->andWhere('u.isUsed = :isUsed')
            ->andWhere('u.expiresAt > :now')
            ->setParameter('phone', $phone)
            ->setParameter('code', $code)
            ->setParameter('isUsed', false)
            ->setParameter('now', new \DateTime())
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
