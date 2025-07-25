<?php

namespace App\Repository;

use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Media>
 *
 * @method Media|null find($id, $lockMode = null, $lockVersion = null)
 * @method Media|null findOneBy(array $criteria, array $orderBy = null)
 * @method Media[]    findAll()
 * @method Media[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }


public function findByAlbumQuery($album)
{
    return $this->createQueryBuilder('m')
        ->leftJoin('m.album', 'a')
        ->addSelect('a')
        ->where('m.album = :album')
        ->setParameter('album', $album)
        ->orderBy('m.id', 'ASC')
        ->getQuery();
}

public function findByUserQuery($user)
{
    return $this->createQueryBuilder('m')
        ->leftJoin('m.user', 'u')
        ->addSelect('u')
        ->where('m.user = :user')
        ->setParameter('user', $user)
        ->orderBy('m.id', 'ASC')
        ->getQuery();
}


}
