<?php

namespace App\Repository;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Media>
 *
 * @method Media|null find($id, $lockMode = null, $lockVersion = null)
 * @method Media|null findOneBy(array $criteria, array $orderBy = null)
 * @method Media[]    findAll()
 * @method Media[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 * @method Media|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 */

class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /**
     * @return Query
     */
    public function findByAlbumQuery(Album $album): Query
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.album', 'a')
            ->addSelect('a')
            ->where('m.album = :album')
            ->setParameter('album', $album)
            ->orderBy('m.id', 'ASC')
            ->getQuery();
    }

    /**
     * @return Query
     */
    public function findByUserQuery(User $user): Query
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.album', 'a')
            ->leftJoin('a.user', 'u')
            ->addSelect('a', 'u')
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('m.id', 'ASC')
            ->getQuery();
    }
    public function findAllVisible(): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.user', 'u')
            ->where('u.isBlocked = false')
            ->getQuery()
            ->getResult();
    }

}
