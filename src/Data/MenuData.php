<?php

namespace App\Data;

class MenuData
{
    /**
     * Représentation Backend du menu Angular pour Passe Voyage
     * Sert à synchroniser les accès dans la table Path.
     */
    public static function getMenu(): array
    {
        return [
            [
                'label' => 'Tableau de bord',
                'link' => '/passe-voyage/dashboard',
                'nom' => 'MENU_DASHBOARD'
            ],
            [
                'label' => 'Crédits & Billets',
                'nom' => 'MENU_PARENT_CREDITS_BILLETS',
                'subItems' => [
                    ['label' => 'Demandes de Crédit', 'link' => '/passe-voyage/credits/demandes', 'nom' => 'MENU_CREDITS_DEMANDES'],
                    ['label' => 'Billets Émis', 'link' => '/passe-voyage/credits/billets', 'nom' => 'MENU_CREDITS_BILLETS'],
                    ['label' => 'Paramètres & Plafonds', 'link' => '/passe-voyage/credits/parametres', 'nom' => 'MENU_CREDITS_PARAMETRES']
                ]
            ],
            [
                'label' => 'Recouvrements',
                'nom' => 'MENU_PARENT_RECOUVREMENTS',
                'subItems' => [
                    ['label' => 'Créances en cours', 'link' => '/passe-voyage/recouvrement/creances', 'nom' => 'MENU_RECOUVREMENTS_CREANCES'],
                    ['label' => 'Paiements Reçus', 'link' => '/passe-voyage/recouvrement/paiements', 'nom' => 'MENU_RECOUVREMENTS_PAIEMENTS'],
                    ['label' => 'Relances & Alertes', 'link' => '/passe-voyage/recouvrement/relances', 'nom' => 'MENU_RECOUVREMENTS_RELANCES']
                ]
            ],
            [
                'label' => 'Passagers',
                'nom' => 'MENU_PARENT_PASSAGERS',
                'subItems' => [
                    ['label' => 'Base Passagers', 'link' => '/passe-voyage/passagers/base', 'nom' => 'MENU_PASSAGERS_BASE'],
                    ['label' => 'Historique Voyages', 'link' => '/passe-voyage/passagers/historique', 'nom' => 'MENU_PASSAGERS_HISTORIQUE'],
                    ['label' => 'Blacklist (Bloqués)', 'link' => '/passe-voyage/passagers/blacklist', 'nom' => 'MENU_PASSAGERS_BLACKLIST']
                ]
            ],
            [
                'label' => 'Agents Terrain',
                'nom' => 'MENU_PARENT_AGENTS',
                'subItems' => [
                    ['label' => 'Annuaire', 'link' => '/passe-voyage/agents-terrain/annuaire', 'nom' => 'MENU_AGENTS_ANNUAIRE'],
                    ['label' => 'Affectations', 'link' => '/passe-voyage/agents-terrain/affectations', 'nom' => 'MENU_AGENTS_AFFECTATIONS'],
                    ['label' => 'Performances', 'link' => '/passe-voyage/agents-terrain/performances', 'nom' => 'MENU_AGENTS_PERFORMANCES']
                ]
            ],
            [
                'label' => 'Partenaires',
                'nom' => 'MENU_PARENT_PARTENAIRES',
                'subItems' => [
                    ['label' => 'Compagnies', 'link' => '/passe-voyage/partenaires/compagnies', 'nom' => 'MENU_PARTENAIRES_COMPAGNIES'],
                    ['label' => 'Fonds & Soldes', 'link' => '/passe-voyage/partenaires/fonds', 'nom' => 'MENU_PARTENAIRES_FONDS'],
                    ['label' => 'Facturation', 'link' => '/passe-voyage/partenaires/facturation', 'nom' => 'MENU_PARTENAIRES_FACTURATION']
                ]
            ],
            [
                'label' => 'Gares & Tarifs',
                'nom' => 'MENU_PARENT_GARES',
                'subItems' => [
                    ['label' => 'Villes & Gares', 'link' => '/passe-voyage/referentiel/gares', 'nom' => 'MENU_GARES_VILLES'],
                    ['label' => 'Trajets', 'link' => '/passe-voyage/referentiel/trajets', 'nom' => 'MENU_GARES_TRAJETS'],
                    ['label' => 'Grille Tarifaire', 'link' => '/passe-voyage/referentiel/tarifs', 'nom' => 'MENU_GARES_TARIFS']
                ]
            ],
            [
                'label' => 'Administration',
                'nom' => 'MENU_PARENT_ADMINISTRATION',
                'subItems' => [
                    ['label' => 'Utilisateurs & Accès', 'link' => '/passe-voyage/administration/users', 'nom' => 'MENU_ADMINISTRATION_USERS'],
                    ['label' => 'Rôles & Permissions', 'link' => '/passe-voyage/administration/roles', 'nom' => 'MENU_ADMINISTRATION_ROLES'],
                    ['label' => 'Paramètres Globaux', 'link' => '/passe-voyage/administration/settings', 'nom' => 'MENU_ADMINISTRATION_SETTINGS']
                ]
            ]
        ];
    }
}