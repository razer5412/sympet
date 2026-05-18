<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'cart_index')]
    public function index(SessionInterface $session, ProduitRepository $repo): Response
    {
        $sessionPanier = $session->get('panier', []);
        $panier = [];
        $totalItems = 0;
        $totalPrix = 0;

        if (!empty($sessionPanier)) {
            $produits = $repo->findBy(['id' => array_keys($sessionPanier)]);

            foreach ($produits as $produit) {
                $id = $produit->getId();
                $qty = $sessionPanier[$id] ?? 0;

                if ($qty <= 0) {
                    continue;
                }

                $itemPrix = (float) $produit->getPrix();
                $itemTotal = $itemPrix * $qty;

                $panier[$id] = [
                    'id' => $id,
                    'nom' => $produit->getNom(),
                    'prix' => $itemPrix,
                    'image' => $produit->getImage(),
                    'stock' => $produit->getStock(),
                    'qty' => $qty,
                    'total' => $itemTotal,
                ];

                $totalItems += $qty;
                $totalPrix += $itemTotal;
            }
        }

        return $this->render('cart/index.html.twig', [
            'panier' => $panier,
            'totalItems' => $totalItems,
            'totalPrix' => $totalPrix,
        ]);
    }

    #[Route('/cart/add/{id}', name: 'cart_add')]
    public function add($id, ProduitRepository $repo, SessionInterface $session)
    {
        $produit = $repo->find($id);

        if (!$produit) {
            throw $this->createNotFoundException('Produit introuvable');
        }

        if ($produit->getStock() <= 0) {
            $this->addFlash('warning', 'Ce produit est en rupture de stock.');
            return $this->redirectToRoute('cart_index');
        }

        $panier = $session->get('panier', []);
        $quantity = ($panier[$id] ?? 0) + 1;

        if ($quantity > $produit->getStock()) {
            $quantity = $produit->getStock();
            $this->addFlash('warning', 'Quantité maximale atteinte pour ce produit.');
        }

        $panier[$id] = $quantity;
        $session->set('panier', $panier);

        return $this->redirectToRoute('cart_index');
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove')]
    public function remove($id, SessionInterface $session)
    {
        $panier = $session->get('panier', []);

        unset($panier[$id]);

        $session->set('panier', $panier);

        return $this->redirectToRoute('cart_index');
    }

    #[Route('/cart/decrease/{id}', name: 'cart_decrease')]
    public function decrease($id, SessionInterface $session)
    {
        $panier = $session->get('panier', []);

        if (isset($panier[$id])) {
            if (is_int($panier[$id])) {
                $panier[$id] = ['qty' => $panier[$id]];
            }

            if ($panier[$id]['qty'] > 1) {
                $panier[$id]['qty']--;
            } else {
                unset($panier[$id]);
            }
        }

        $session->set('panier', $panier);

        return $this->redirectToRoute('cart_index'); // ← replace with your cart route name
    }

    #[Route('/cart/clear', name: 'cart_clear')]
    public function clear(SessionInterface $session)
    {
        $session->remove('panier');

        return $this->redirectToRoute('cart_index');
    }
}
