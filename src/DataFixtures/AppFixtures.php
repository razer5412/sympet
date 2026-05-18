<?php

namespace App\DataFixtures;

use App\Entity\Categorie;
use App\Entity\Produit;
use App\Entity\User;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // ── 1. Categories ───────────────────────────────────────────────
        $categorieNames = [
            'Alimentation', 'Jouets', 'Accessoires', 'Hygiène', 'Santé', 'Literie',
        ];

        $categories = [];
        foreach ($categorieNames as $nom) {
            $categorie = new Categorie();
            $categorie->setNom($nom);
            $manager->persist($categorie);
            $categories[] = $categorie;
        }

        // ── 2. Products ─────────────────────────────────────────────────
        $petProducts = [
            'Alimentation' => [
                'Croquettes Premium Chien', 'Pâtée Royale Chat', 'Friandises Lapin',
                'Granulés Perroquet', 'Nourriture Poisson Tropical', 'Foin Timothy',
            ],
            'Jouets' => [
                'Balle en caoutchouc', 'Souris en peluche', 'Tunnel chat',
                'Corde à mâcher', 'Laser interactif', 'Plume à ressort',
            ],
            'Accessoires' => [
                'Collier réfléchissant', 'Laisse extensible 5m', 'Harnais confort',
                'Cage de transport', 'Fontaine à eau', 'Gamelle anti-glouton',
            ],
            'Hygiène' => [
                'Shampooing doux chien', 'Brosse démêlante', 'Coupe-ongles',
                'Dentifrice animal', 'Lingettes nettoyantes', 'Anti-puces spray',
            ],
            'Santé' => [
                'Complément articulaire', 'Vitamine C rongeur', 'Pipette anti-tiques',
                'Probiotiques digestifs', 'Huile de saumon', 'Spray cicatrisant',
            ],
            'Literie' => [
                'Coussin orthopédique', 'Panier osier naturel', 'Couverture polaire',
                'Hamac pour chat', 'Niche bois extérieur', 'Tapis griffoir',
            ],
        ];

        $produits = [];
        foreach ($categories as $categorie) {
            $noms = $petProducts[$categorie->getNom()] ?? [];
            foreach ($noms as $nom) {
                $produit = new Produit();
                $produit->setNom($nom);
                $produit->setPrix($faker->randomFloat(2, 3.99, 149.99));
                $produit->setStock($faker->numberBetween(0, 100));
                $produit->setDescription($faker->sentences(3, true));
                $produit->setImage(null); // set a real path if you have images
                $produit->setCategorie($categorie);
                $manager->persist($produit);
                $produits[] = $produit;
            }
        }

        // ── 3. Users ────────────────────────────────────────────────────
        $users = [];

        // One fixed admin
        $admin = new User();
        $admin->setEmail('admin@sympet.tn');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'admin123')
        );
        $manager->persist($admin);
        $users[] = $admin;

        // 9 random customers
        for ($i = 0; $i < 9; $i++) {
            $user = new User();
            $user->setEmail($faker->unique()->safeEmail());
            $user->setRoles(['ROLE_USER']);
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, 'password123')
            );
            $manager->persist($user);
            $users[] = $user;
        }

        // ── 4. Commandes + LigneCommandes ───────────────────────────────
        $statuts = ['en_attente', 'confirmée', 'expédiée', 'livrée', 'annulée'];

        for ($i = 0; $i < 20; $i++) {
            $commande = new Commande();
            $commande->setStatut($faker->randomElement($statuts));
            $commande->setCreatedAt(
                \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-6 months', 'now'))
            );
            $commande->setUser($faker->randomElement($users));

            $total = 0;
            $nbLignes = $faker->numberBetween(1, 5);
            for ($j = 0; $j < $nbLignes; $j++) {
                $ligne = new LigneCommande();
                $produit = $faker->randomElement($produits);
                $quantite = $faker->numberBetween(1, 4);
                $prixUnitaire = $produit->getPrix();

                $ligne->setProduit($produit);
                $ligne->setQuantite($quantite);
                $ligne->setPrixUnitaire($prixUnitaire);
                $ligne->setCommande($commande);

                $total += $quantite * $prixUnitaire;
                $manager->persist($ligne);
            }

            $commande->setTotal(round($total, 2));
            $manager->persist($commande);
        }

        $manager->flush();
    }
}