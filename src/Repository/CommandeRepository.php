<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }
    public function getChiffreAffaires(): float
    {
        return (float) $this->createQueryBuilder('c')
            ->select('SUM(c.total)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    public function getCommandesParStatut(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.statut, COUNT(c.id) as total')
            ->groupBy('c.statut')
            ->getQuery()
            ->getResult();
    }
    public function getCommandesParMois(): array
    {
        $commandes = $this->createQueryBuilder('c')
            ->select('c')
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($commandes as $commande) {
            if (!$commande instanceof Commande) {
                continue;
            }

            $createdAt = $commande->getCreatedAt();
            if (!$createdAt) {
                continue;
            }

            $month = $createdAt->format('Y-m');
            $counts[$month] = ($counts[$month] ?? 0) + 1;
        }

        ksort($counts);

        return array_map(fn($mois, $total) => ['mois' => $mois, 'total' => $total], array_keys($counts), $counts);
    }
    public function getProduitsLesPlusVendus(int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->select('p.nom as nom, SUM(l.quantite) as totalVendu')
            ->join('c.ligneCommandes', 'l')
            ->join('l.produit', 'p')
            ->groupBy('p.id')
            ->orderBy('totalVendu', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Commande[] Returns an array of Commande objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Commande
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
