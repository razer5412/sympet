<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    public function search(string $q): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c')
            ->where('p.nom LIKE :q')
            ->setParameter('q', '%' . $q . '%')
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function getProduitsLesPlusVendus(int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->select('p.nom as nom, SUM(l.quantite) as totalVendu')
            ->join('c.lignes', 'l')
            ->join('l.produit', 'p')
            ->groupBy('p.id')
            ->orderBy('totalVendu', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findAllWithCategory(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c')
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findWithCategory(int $id): ?Produit
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function createFilteredQuery(?string $minPrix, ?string $maxPrix, ?int $categorieId)
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c');

        if ($minPrix !== null && $minPrix !== '') {
            $qb->andWhere('p.prix >= :minPrix')
                ->setParameter('minPrix', $minPrix);
        }

        if ($maxPrix !== null && $maxPrix !== '') {
            $qb->andWhere('p.prix <= :maxPrix')
                ->setParameter('maxPrix', $maxPrix);
        }

        if ($categorieId !== null) {
            $qb->andWhere('c.id = :categorieId')
                ->setParameter('categorieId', $categorieId);
        }

        return $qb->orderBy('p.nom', 'ASC')
            ->getQuery();
    }

    public function createListQuery()
    {
        return $this->createFilteredQuery(null, null, null);
    }
}
