<?php

namespace App\Repository;

use App\Entity\Upload;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Upload|null find($id, $lockMode = null, $lockVersion = null)
 * @method Upload|null findOneBy(array $criteria, array $orderBy = null)
 * @method Upload[]    findAll()
 * @method Upload[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UploadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Upload::class);
    }

    /**
     * @param integer $userId
     * @param integer $currentPage
     * @param integer $limit
     * @return Upload[] Returns an array of Upload objects
     */
    public function findByUser($userId, $currentPage = 1, $limit = 10)
    {
        $query = $this->createQueryBuilder('u')
            ->andWhere('u.user = :user')
            ->setParameter('user', $userId)
            ->orderBy('u.id', 'ASC')
            ->getQuery();
        $paginator = $this->paginate($query, $currentPage, $limit);
        return $paginator;
    }

    /**
     * @param Query $dql
     * @param integer $page
     * @param integer $limit
     * @return Paginator Returns a Paginator object
     */
    public function paginate($dql, $page = 1, $limit = 10)
    {
        $paginator = new Paginator($dql);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);
        return $paginator;
    }

    /**
     * @param integer $id
     * @param integer $userId
     * @return Upload Returns an Upload object
     */
    public function findOneById($id, $userId): ?Upload
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.id = :id')
            ->andWhere('u.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

}
