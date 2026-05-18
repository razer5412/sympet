<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use App\Repository\CategorieRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ListerController extends AbstractController
{
    #[Route('/liste', name: 'app_lister')]
    public function index(
        Request $request,
        ProduitRepository $produitRepo,
        CategorieRepository $categorieRepo,
        PaginatorInterface $paginator
    ): Response {
        $minPrix = $request->query->get('min_price');
        $maxPrix = $request->query->get('max_price');
        $categorieId = $request->query->getInt('category');
        if ($categorieId === 0) {
            $categorieId = null;
        }

        $query = $produitRepo->createFilteredQuery($minPrix, $maxPrix, $categorieId);

        $produits = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('lister/index.html.twig', [
            'produits' => $produits,
            'categories' => $categorieRepo->findAll(),
        ]);
    }

    #[Route('/detail/{id}', name: 'app_detail')]
    public function detail(
        ProduitRepository $produitRepo,
        int $id
    ): Response {
        $produit = $produitRepo->findWithCategory($id);

        if (!$produit) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        return $this->render('lister/detail.html.twig', [
            'produit' => $produit,
        ]);
    }
}
