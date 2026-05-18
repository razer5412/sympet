<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use App\Repository\CategorieRepository;
use App\Repository\CommandeRepository;
use App\Repository\UserRepository;
use App\Entity\Produit;
use App\Entity\Categorie;
use App\Form\ProduitType;
use App\Form\CategorieType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    // ── Dashboard ────────────────────────────────────────────────────
    #[Route('/admin_dashboard', name: 'admin_dashboard')]
    public function dashboard(
        ProduitRepository $produitRepo,
        CommandeRepository $commandeRepo,
        UserRepository $userRepo
    ): Response {
        // Chiffre d'affaires total
        $chiffreAffaires = $commandeRepo->getChiffreAffaires();

        // Commandes par statut
        $commandesParStatut = $commandeRepo->getCommandesParStatut();

        // Commandes par mois (6 derniers mois)
        $commandesParMois = $commandeRepo->getCommandesParMois();

        // Produits les plus vendus
        $produitsLesPlusVendus = $commandeRepo->getProduitsLesPlusVendus(5);

        // Compteurs
        $totalClients  = $userRepo->count([]);
        $totalProduits = $produitRepo->count([]);
        $totalCommandes = $commandeRepo->count([]);

        return $this->render('admin/dashboard.html.twig', [
            'chiffreAffaires'       => $chiffreAffaires,
            'commandesParStatut'    => $commandesParStatut,
            'commandesParMois'      => $commandesParMois,
            'produitsLesPlusVendus' => $produitsLesPlusVendus,
            'totalClients'          => $totalClients,
            'totalProduits'         => $totalProduits,
            'totalCommandes'        => $totalCommandes,
        ]);
    }

    // ── Produits ─────────────────────────────────────────────────────
    #[Route('/produits', name: 'admin_produits')]
    public function produits(ProduitRepository $repo): Response
    {
        return $this->render('admin/produits/index.html.twig', [
            'produits' => $repo->findAllWithCategory(),
        ]);
    }

    #[Route('/produits/new', name: 'admin_produit_new')]
    public function produitNew(Request $request, EntityManagerInterface $em): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($produit);
            $em->flush();
            $this->addFlash('success', 'Produit ajouté avec succès.');
            return $this->redirectToRoute('admin_produits');
        }

        return $this->render('admin/produits/form.html.twig', [
            'form'  => $form,
            'titre' => 'Ajouter un produit',
        ]);
    }

    #[Route('/produits/{id}/edit', name: 'admin_produit_edit')]
    public function produitEdit(Produit $produit, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Produit modifié avec succès.');
            return $this->redirectToRoute('admin_produits');
        }

        return $this->render('admin/produits/form.html.twig', [
            'form'  => $form,
            'titre' => 'Modifier le produit',
        ]);
    }

    #[Route('/produits/{id}/delete', name: 'admin_produit_delete', methods: ['POST'])]
    public function produitDelete(Produit $produit, EntityManagerInterface $em): Response
    {
        $em->remove($produit);
        $em->flush();
        $this->addFlash('success', 'Produit supprimé.');
        return $this->redirectToRoute('admin_produits');
    }

    // ── Catégories ───────────────────────────────────────────────────
    #[Route('/categories', name: 'admin_categories')]
    public function categories(CategorieRepository $repo): Response
    {
        return $this->render('admin/categories/index.html.twig', [
            'categories' => $repo->findAll(),
        ]);
    }

    #[Route('/categories/new', name: 'admin_categorie_new')]
    public function categorieNew(Request $request, EntityManagerInterface $em): Response
    {
        $categorie = new Categorie();
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($categorie);
            $em->flush();
            $this->addFlash('success', 'Catégorie créée.');
            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('admin/categories/form.html.twig', [
            'form'  => $form,
            'titre' => 'Nouvelle catégorie',
        ]);
    }

    #[Route('/categories/{id}/edit', name: 'admin_categorie_edit')]
    public function categorieEdit(Categorie $categorie, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Catégorie modifiée.');
            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('admin/categories/form.html.twig', [
            'form'  => $form,
            'titre' => 'Modifier la catégorie',
        ]);
    }

    #[Route('/categories/{id}/delete', name: 'admin_categorie_delete', methods: ['POST'])]
    public function categorieDelete(Categorie $categorie, EntityManagerInterface $em): Response
    {
        $em->remove($categorie);
        $em->flush();
        $this->addFlash('success', 'Catégorie supprimée.');
        return $this->redirectToRoute('admin_categories');
    }

    // ── Commandes ────────────────────────────────────────────────────
    #[Route('/commandes', name: 'admin_commandes')]
    public function commandes(CommandeRepository $repo): Response
    {
        return $this->render('admin/commandes/index.html.twig', [
            'enAttente'  => $repo->findBy(['statut' => 'en_attente']),
            'confirmees' => $repo->findBy(['statut' => 'confirmée']),
            'livrees'    => $repo->findBy(['statut' => 'livrée']),
        ]);
    }

    // ── Utilisateurs ─────────────────────────────────────────────────
    #[Route('/utilisateurs', name: 'admin_users')]
    public function users(UserRepository $repo): Response
    {
        return $this->render('admin/users/index.html.twig', [
            'users' => $repo->findAll(),
        ]);
    }

    #[Route('/utilisateurs/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function userDelete(int $id, UserRepository $repo, EntityManagerInterface $em): Response
    {
        $user = $repo->find($id);
        if ($user) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé.');
        }
        return $this->redirectToRoute('admin_users');
    }
}
