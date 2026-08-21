<?php

namespace App\Repository\Business;

use App\Entity\Business\CompanyFund;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompanyFund>
 *
 * @method CompanyFund|null find($id, $lockMode = null, $lockVersion = null)
 * @method CompanyFund|null findOneBy(array $criteria, array $orderBy = null)
 * @method CompanyFund[]    findAll()
 * @method CompanyFund[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyFundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanyFund::class);
    }
}
