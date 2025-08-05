<?php

namespace App\Repository;

use App\Entity\Books;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Books>
 */
class BooksRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Books::class);
    }

    /**
     * Recherche les livres par titre ou auteur pour un utilisateur donné (et optionnellement un thème)
     *
     * @param string|null $query
     * @param User $user
     * @param Themes|null $theme
     * @return Books[]
     */
    public function searchBooks(?string $query, $user, $theme = null): array
    {
        $qb = $this->createQueryBuilder('b')
            ->where('b.user = :user')
            ->setParameter('user', $user);
        if ($theme !== null) {
            $qb->andWhere('b.theme = :theme')
                ->setParameter('theme', $theme);
        }
        if ($query) {
            $qb->andWhere('LOWER(b.titre) LIKE :q1 OR LOWER(b.titre) LIKE :q2 OR LOWER(b.auteur) LIKE :q1 OR LOWER(b.auteur) LIKE :q2')
                ->setParameter('q1', mb_strtolower($query) . '%')
                ->setParameter('q2', '%' . mb_strtolower($query) . '%');
        }
        $qb->orderBy('b.id', 'DESC');
        return $qb->getQuery()->getResult();
    }

    //    public function findOneBySomeField($value): ?Books
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}