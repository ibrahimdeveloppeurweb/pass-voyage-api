<?php

namespace App\Manager\Admin;

use App\Repository\Admin\AuditLogRepository;
use App\Repository\CompanyRepository;
use App\Repository\CreditRequestRepository;
use Doctrine\ORM\EntityManagerInterface;

class DashboardManager
{
    private $companyRepository;
    private $creditRequestRepository;
    private $auditLogRepository;
    private $em;

    public function __construct(
        CompanyRepository $companyRepository,
        CreditRequestRepository $creditRequestRepository,
        AuditLogRepository $auditLogRepository,
        EntityManagerInterface $em
    )
    {
        $this->companyRepository = $companyRepository;
        $this->creditRequestRepository = $creditRequestRepository;
        $this->auditLogRepository = $auditLogRepository;
        $this->em = $em;
    }

    public function getMainDashboardData(int $months = 6): array
    {
        $conn = $this->em->getConnection();

        // 1. Stats globales
        $sqlCompanies = "SELECT COUNT(id) FROM company WHERE deleted_at IS NULL";
        $totalCompanies = (int)$conn->fetchOne($sqlCompanies);

        $sqlRequests = "SELECT COUNT(id) FROM credit_request WHERE deleted_at IS NULL";
        $totalRequests = (int)$conn->fetchOne($sqlRequests);

        $sqlPendingRequests = "SELECT COUNT(id) FROM credit_request WHERE deleted_at IS NULL AND UPPER(status) = 'PENDING'";
        $pendingRequests = (int)$conn->fetchOne($sqlPendingRequests);

        $sqlApprovedAmount = "SELECT SUM(amount) FROM credit_request WHERE deleted_at IS NULL AND UPPER(status) = 'APPROVED'";
        $approvedAmount = (float)$conn->fetchOne($sqlApprovedAmount);

        $stats = [
            'totalCompanies' => $totalCompanies,
            'totalRequests' => $totalRequests,
            'pendingRequests' => $pendingRequests,
            'approvedAmount' => $approvedAmount,
        ];

        // 2. Demandes récentes
        $sqlRecent = "
            SELECT cr.uuid, cr.amount, cr.status, cr.motif, cr.created_at, c.name as company_name
            FROM credit_request cr
            LEFT JOIN company c ON cr.company_id = c.id
            WHERE cr.deleted_at IS NULL
            ORDER BY cr.created_at DESC
            LIMIT 8
        ";
        $recentRaw = $conn->fetchAllAssociative($sqlRecent);
        $recentRequests = array_map(function ($row) {
            return [
                'id' => $row['uuid'],
                'company' => $row['company_name'] ?: 'Inconnu',
                'amount' => $row['amount'],
                'status' => strtolower($row['status']),
                'motif' => $row['motif'],
                'date' => $row['created_at']
            ];
        }, $recentRaw);

        // 3. Activité Récente (Audit)
        $logs = $this->auditLogRepository->findBy([], ['id' => 'DESC'], 5);
        $recentActivity = [];
        foreach ($logs as $log) {
            $recentActivity[] = [
                'user' => $log->getUserFullName() ?: 'Système',
                'time' => $log->getCreatedAt()->format('H:i d/m'),
                'description' => $log->getAction() . ' : ' . $log->getModule()
            ];
        }

        // 4. Évolution des demandes approuvées (Graphique)
        $groupByYear = $months > 36;
        $datePart = $groupByYear ? '%Y' : '%Y-%m';
        $startDate = (new \DateTime())->modify("-{$months} months")->format('Y-m-01');

        $sqlCashflow = "
            SELECT 
                DATE_FORMAT(cr.created_at, '{$datePart}') as period, 
                SUM(cr.amount) as total
            FROM credit_request cr
            WHERE cr.deleted_at IS NULL
              AND cr.created_at >= ?
              AND UPPER(cr.status) = 'APPROVED'
            GROUP BY DATE_FORMAT(cr.created_at, '{$datePart}')
            ORDER BY period ASC
        ";
        $cashflowRaw = $conn->fetchAllAssociative($sqlCashflow, [$startDate]);
        $revenueChartData = [];
        foreach ($cashflowRaw as $row) {
            $revenueChartData[] = [
                'month' => $row['period'],
                'paid' => (float)$row['total']
            ];
        }

        return [
            'stats' => $stats,
            'recentRequests' => $recentRequests,
            'recentActivity' => $recentActivity,
            'revenueChartData' => $revenueChartData
        ];
    }

    public function getAdminDashboardData(): array
    {
        $conn = $this->em->getConnection();

        // 1. KPIs
        $sqlTotalAudit = "SELECT COUNT(id) FROM audit_log WHERE DATE(created_at) = CURRENT_DATE()";
        $totalAuditActions = (int)$conn->fetchOne($sqlTotalAudit);

        $sqlActiveCollabs = "SELECT COUNT(DISTINCT user_full_name) FROM audit_log WHERE DATE(created_at) = CURRENT_DATE()";
        $activeCollaborators = (int)$conn->fetchOne($sqlActiveCollabs);

        $sqlPendingApprovals = "SELECT COUNT(id) FROM credit_request WHERE deleted_at IS NULL AND UPPER(status) = 'PENDING'";
        $pendingApprovals = (int)$conn->fetchOne($sqlPendingApprovals);

        // 2. Pending Tasks List
        $pendingTasks = [];
        $sqlRawPending = "
            SELECT cr.uuid, cr.amount, cr.created_at, c.name as company_name
            FROM credit_request cr
            LEFT JOIN company c ON cr.company_id = c.id
            WHERE cr.deleted_at IS NULL AND UPPER(cr.status) = 'PENDING'
            ORDER BY cr.created_at ASC LIMIT 5
        ";
        $resPending = $conn->fetchAllAssociative($sqlRawPending);
        foreach ($resPending as $row) {
            $pendingTasks[] = [
                'id' => $row['uuid'],
                'type' => 'Validation Demande',
                'description' => 'Demande de ' . number_format($row['amount'], 0, ',', ' ') . ' XOF à valider',
                'requester' => trim($row['company_name']) ?: 'Inconnu',
                'date' => $row['created_at'],
                'priority' => 'high'
            ];
        }

        // 3. Audit Logs
        $auditLogsRaw = $this->auditLogRepository->findBy([], ['id' => 'DESC'], 10);
        $auditLogs = [];
        foreach ($auditLogsRaw as $log) {
            $auditLogs[] = [
                'user' => $log->getUserFullName() ?: 'Système',
                'avatar' => $log->getUserInitials() ?: 'SY',
                'module' => $log->getModule(),
                'action' => $log->getAction(),
                'details' => $log->getDetails(),
                'time' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
                'status' => str_contains(strtolower($log->getDetails() ?? ''), 'erreur') ? 'Alerte' : 'Succès'
            ];
        }

        // 4. Charts - Distribution des types d'actions
        $sqlActionDist = "SELECT action, COUNT(id) as count FROM audit_log GROUP BY action";
        $resActionDist = $conn->fetchAllAssociative($sqlActionDist);
        $actionLabels = [];
        $actionSeries = [];
        foreach ($resActionDist as $row) {
            $actionLabels[] = $row['action'];
            $actionSeries[] = (int)$row['count'];
        }

        // Collaborator Activity - Top 6
        $sqlCollabActivity = "
            SELECT user_full_name, 
                   COUNT(id) as total_actions,
                   SUM(CASE WHEN action = 'Validation' OR action LIKE 'Validation%' THEN 1 ELSE 0 END) as processed
            FROM audit_log 
            WHERE user_full_name IS NOT NULL
            GROUP BY user_full_name
            ORDER BY total_actions DESC
            LIMIT 6
        ";
        $resCollabActivity = $conn->fetchAllAssociative($sqlCollabActivity);
        $collabNames = [];
        $collabTotalArr = [];
        $collabProcessedArr = [];
        $collabRequestedArr = [];

        $sqlCollabReq = "
            SELECT user_full_name, COUNT(id) as requested
            FROM audit_log
            WHERE action IN ('Création')
            GROUP BY user_full_name
        ";
        $reqMapArr = [];
        foreach ($conn->fetchAllAssociative($sqlCollabReq) as $r) {
            $reqMapArr[$r['user_full_name']] = (int)$r['requested'];
        }

        foreach ($resCollabActivity as $row) {
            $collabNames[] = $row['user_full_name'];
            $collabTotalArr[] = (int)$row['total_actions'];
            $collabProcessedArr[] = (int)$row['processed'];
            $collabRequestedArr[] = $reqMapArr[$row['user_full_name']] ?? 0;
        }

        $result = [
            'stats' => [
                'totalAuditActions' => $totalAuditActions,
                'pendingApprovals' => $pendingApprovals,
                'criticalAnomalies' => 0,
                'activeCollaborators' => $activeCollaborators
            ],
            'pendingTasks' => $pendingTasks,
            'auditLogs' => $auditLogs,
            'auditActionChart' => [
                'series' => $actionSeries,
                'labels' => $actionLabels
            ],
            'approvalTrendChart' => [
                'requested' => $collabRequestedArr,
                'processed' => $collabProcessedArr,
                'totalActions' => $collabTotalArr,
                'collaborators' => $collabNames
            ]
        ];

        return $this->ensureUtf8Recursive($result);
    }

    /**
     * Helper to ensure all strings in an array are valid UTF-8
     */
    private function ensureUtf8Recursive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->ensureUtf8Recursive($value);
            }
            elseif (is_string($value)) {
                if (!mb_check_encoding($value, 'UTF-8')) {
                    $data[$key] = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
                }
            }
        }
        return $data;
    }
}