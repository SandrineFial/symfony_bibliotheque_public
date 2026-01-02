<?php

namespace App\Repository;

use App\Entity\Books;
use App\Entity\User;
use App\Entity\Themes;
use App\Entity\SousThemes;
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
     * @param string|null $type
     * @param Themes|null $theme
     * @param SousThemes|null $sousTheme
     * @return Books[]
     */
    public function searchBooks(?string $query, User $user, ?string $type = null, ?Themes $theme = null, ?SousThemes $sousTheme = null): array
    {
        $qb = $this->createQueryBuilder('b')
            ->where('b.user = :user')
            ->setParameter('user', $user);
        if ($theme !== null) {
            $qb->andWhere('b.theme = :theme')
                ->setParameter('theme', $theme);
        }
        if ($sousTheme !== null) {
            $qb->andWhere('b.sousTheme = :sousTheme')
                ->setParameter('sousTheme', $sousTheme);
        }
        if ($query) {
            $field = in_array($type, ['titre', 'auteur', 'edition']) ? $type : 'titre';
            if ($field === 'auteur') {
                $parts = array_filter(explode(' ', mb_strtolower($query)));
                foreach ($parts as $i => $part) {
                    $qb->andWhere("LOWER(b.auteur) LIKE :auteur_part_$i")
                    ->setParameter("auteur_part_$i", '%' . $part . '%');
                }
            } elseif ($field === 'edition') {
                $qb->andWhere("(LOWER(b.edition) LIKE :search_start OR LOWER(b.edition) LIKE :search_end OR LOWER(b.edition) LIKE :search_center) OR (LOWER(b.editionDetail) LIKE :search_start OR LOWER(b.editionDetail) LIKE :search_end OR LOWER(b.editionDetail) LIKE :search_center)")
                    ->setParameter('search_start', mb_strtolower($query) . '%')
                    ->setParameter('search_end', '%' . mb_strtolower($query))
                    ->setParameter('search_center', '%' . mb_strtolower($query) . '%');
            } else {
                $qb->andWhere("LOWER(b.$field) LIKE :search_start OR LOWER(b.$field) LIKE :search_end OR LOWER(b.$field) LIKE :search_center")
                    ->setParameter('search_start', mb_strtolower($query) . '%')
                    ->setParameter('search_end', '%' . mb_strtolower($query))
                    ->setParameter('search_center', '%' . mb_strtolower($query) . '%');
            }
            /*
            if ($field === 'auteur') {
                $parts = explode(' ', mb_strtolower($query));
                if (count($parts) >= 2) {
                    $nom = $parts[0];
                    $prenom = $parts[1];
                    $qb->andWhere(
                        "(LOWER(b.auteur) LIKE :nom_prenom_start OR LOWER(b.auteur) LIKE :prenom_nom_start OR LOWER(b.auteur) LIKE :nom_start OR LOWER(b.auteur) LIKE :prenom_start)"
                    )
                    ->setParameter('nom_prenom_start', $nom . ' ' . $prenom . '%')
                    ->setParameter('prenom_nom_start', $prenom . ' ' . $nom . '%')
                    ->setParameter('nom_start', $nom . '%')
                    ->setParameter('prenom_start', $prenom . '%');
                } else {
                    $qb->andWhere("LOWER(b.auteur) LIKE :search_center")
                        ->setParameter('search_center', '%' . mb_strtolower($query) . '%');
                }
            } else {
                $qb->andWhere("LOWER(b.$field) LIKE :search_start OR LOWER(b.$field) LIKE :search_end OR LOWER(b.$field) LIKE :search_center")
                    ->setParameter('search_start', mb_strtolower($query) . '%')
                    ->setParameter('search_end', '%' . mb_strtolower($query))
                    ->setParameter('search_center', '%' . mb_strtolower($query) . '%');
            }*/
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