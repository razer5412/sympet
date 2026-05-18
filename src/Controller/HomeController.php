<?php

namespace App\Controller;
use App\Repository\ProduitRepository;
use App\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
class HomeController extends AbstractController
{
   #[Route('/home', name: 'home')]
public function index(): Response
{
    return $this->render('home/index.html.twig');
}
    #[Route('/produits/search', name: 'app_produits_search')]
public function searchAjax(
    Request $request,
    ProduitRepository $produitRepo
): Response {
    $q = $request->query->get('q', '');

    $produits = $q !== '' ? $produitRepo->search($q) : [];

    return $this->json(
        array_map(fn($p) => [
            'id'    => $p->getId(),
            'nom'   => $p->getNom(),
            'prix'  => $p->getPrix(),
            'stock' => $p->getStock(),
            'categorie' => $p->getCategorie()?->getNom(),
        ], $produits)
    );
}
}