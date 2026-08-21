<?php

namespace App\Helpers;

/**
 * RouteHelper - Aide à la gestion des permissions de routes Passe Voyage.
 *
 * Ce helper regroupe les routes API par domaine fonctionnel.
 * Il est utilisé dans UserFixture et RoleManager pour attribuer
 * les bons droits d'accès aux rôles de la plateforme.
 *
 * Architecture des routes Passe Voyage :
 * - /api/private/admin/...     → Routes d'administration (gestion des utilisateurs, rôles, etc.)
 * - /api/private/credit/...    → Gestion des demandes de crédits
 * - /api/private/company/...   → Gestion des entreprises / partenaires
 * - /api/private/repayment/... → Gestion des recouvrements et remboursements
 * - /api/private/ticket/...    → Gestion des billets émis
 * - /api/private/extra/...     → Paramètres et fonctionnalités transversales
 */
class RouteHelper
{
    // =========================================================================
    // MÉTHODES UTILITAIRES INTERNES
    // =========================================================================

    /**
     * Filtre les routes en excluant celles correspondant aux actions sensibles.
     * Actions exclues : delete, validate, activate
     */
    private static function excludeSensitiveActions(array $paths): array
    {
        return array_values(array_filter($paths, function ($path) {
            return !preg_match('#delete|validate|activate#', $path->getNom());
        }));
    }

    /**
     * Filtre une liste de routes à partir d'un tableau de patterns regex.
     */
    private static function filterByPatterns(array $paths, array $patterns): array
    {
        $result = [];
        foreach ($paths as $path) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $path->getChemin())) {
                    $result[] = $path;
                    break;
                }
            }
        }
        return $result;
    }

    // =========================================================================
    // SUPER ADMIN — Accès complet à toutes les routes privées
    // =========================================================================

    /**
     * Toutes les routes privées de l'administration.
     * Réservé au Super Administrateur Passe Voyage.
     */
    public static function ADMIN_ROUTE(array $paths): array
    {
        return self::filterByPatterns($paths, [
            '#^/api/private#',
            '#^/printer#',
            '#^/admin#',
        ]);
    }

    // =========================================================================
    // AGENT TERRAIN / COLLABORATEUR — Accès opérationnel
    // =========================================================================

    /**
     * Routes accessibles au Collaborateur Interne (Agent Terrain, etc.).
     * Accès complet aux modules métier, sans gestion des droits d'accès.
     */
    public static function AGENT_ROUTE(array $paths): array
    {
        return self::filterByPatterns($paths, [
            '#^/api/private/credit#',
            '#^/api/private/company#',
            '#^/api/private/repayment#',
            '#^/api/private/ticket#',
            '#^/api/private/extra/shared#',
        ]);
    }

    /**
     * Routes Agent avec actions sensibles bloquées (delete, validate, activate).
     * Pour un Agent en mode lecture/écriture sans suppression ou validation de crédit.
     */
    public static function AGENT_RESTRICTED_ROUTE(array $paths): array
    {
        return self::excludeSensitiveActions(self::AGENT_ROUTE($paths));
    }

    // =========================================================================
    // CREDITS — Gestion des Demandes de Crédit
    // =========================================================================

    /**
     * Routes liées à la gestion des crédits.
     */
    public static function CREDIT_ROUTE(array $paths): array
    {
        return self::filterByPatterns($paths, [
            '#^/api/private/credit#',
        ]);
    }

    /**
     * Routes Crédits sans actions sensibles.
     */
    public static function CREDIT_RESTRICTED_ROUTE(array $paths): array
    {
        return self::excludeSensitiveActions(self::CREDIT_ROUTE($paths));
    }

    // =========================================================================
    // ENTREPRISES / PARTENAIRES — Gestion des clients
    // =========================================================================

    /**
     * Routes liées aux entreprises partenaires.
     */
    public static function COMPANY_ROUTE(array $paths): array
    {
        return self::filterByPatterns($paths, [
            '#^/api/private/company#',
        ]);
    }

    /**
     * Routes Entreprises sans actions sensibles.
     */
    public static function COMPANY_RESTRICTED_ROUTE(array $paths): array
    {
        return self::excludeSensitiveActions(self::COMPANY_ROUTE($paths));
    }

    // =========================================================================
    // RECOUVREMENTS — Gestion financière
    // =========================================================================

    /**
     * Routes liées aux remboursements.
     */
    public static function REPAYMENT_ROUTE(array $paths): array
    {
        return self::filterByPatterns($paths, [
            '#^/api/private/repayment#',
        ]);
    }

    /**
     * Routes Recouvrements sans actions sensibles.
     */
    public static function REPAYMENT_RESTRICTED_ROUTE(array $paths): array
    {
        return self::excludeSensitiveActions(self::REPAYMENT_ROUTE($paths));
    }

    // =========================================================================
    // BILLETS — Billetterie
    // =========================================================================

    /**
     * Routes liées aux billets émis.
     */
    public static function TICKET_ROUTE(array $paths): array
    {
        return self::filterByPatterns($paths, [
            '#^/api/private/ticket#',
        ]);
    }

    /**
     * Routes Billets sans actions sensibles.
     */
    public static function TICKET_RESTRICTED_ROUTE(array $paths): array
    {
        return self::excludeSensitiveActions(self::TICKET_ROUTE($paths));
    }

    // =========================================================================
    // EXTRA — Routes transversales (paramètres, recherche, notifications)
    // =========================================================================

    /**
     * Routes transversales accessibles à tous les utilisateurs authentifiés.
     */
    public static function EXTRA_ROUTE(array $paths): array
    {
        return self::filterByPatterns($paths, [
            '#^/api/private/extra#',
        ]);
    }

    /**
     * Toutes les routes liées au menu frontend (MenuData).
     */
    public static function MENU_ROUTE(array $paths): array
    {
        return array_values(array_filter($paths, function ($path) {
            return strpos($path->getNom(), 'MENU_') === 0;
        }));
    }
}