<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/api/private/admin/path' => [
            [['_route' => 'index_path', '_controller' => 'App\\Controller\\Admin\\PathController::index'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_path_slash', '_controller' => 'App\\Controller\\Admin\\PathController::index'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/private/admin/role' => [
            [['_route' => 'index_role_admin', '_controller' => 'App\\Controller\\Admin\\RoleController::index'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_role_admin_slash', '_controller' => 'App\\Controller\\Admin\\RoleController::index'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/private/admin/role/show' => [[['_route' => 'role_show_admin', '_controller' => 'App\\Controller\\Admin\\RoleController::show'], null, ['GET' => 0], null, false, false, null]],
        '/api/private/admin/role/new' => [[['_route' => 'new_role_admin', '_controller' => 'App\\Controller\\Admin\\RoleController::new'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/admin/user' => [
            [['_route' => 'index_user_admin', '_controller' => 'App\\Controller\\Admin\\UserController::index'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_user_admin_slash', '_controller' => 'App\\Controller\\Admin\\UserController::index'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/private/admin/user/new' => [[['_route' => 'new_user_admin', '_controller' => 'App\\Controller\\Admin\\UserController::new'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/admin/user/show' => [[['_route' => 'show_user_admin', '_controller' => 'App\\Controller\\Admin\\UserController::show'], null, ['GET' => 0], null, false, false, null]],
        '/api/private/agent' => [[['_route' => 'index_agent', '_controller' => 'App\\Controller\\Business\\AgentController::index'], null, ['GET' => 0], null, true, false, null]],
        '/api/private/agent/new' => [[['_route' => 'new_agent', '_controller' => 'App\\Controller\\Business\\AgentController::new'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/agent/dashboard' => [[['_route' => 'get_agent_dashboard', '_controller' => 'App\\Controller\\Business\\AgentController::getDashboard'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/api/private/agent/ticket/verify' => [[['_route' => 'verify_agent_ticket', '_controller' => 'App\\Controller\\Business\\AgentController::verifyTicket'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/agent/ticket/send-otp' => [[['_route' => 'agent_send_otp', '_controller' => 'App\\Controller\\Business\\AgentController::sendOtp'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/agent/ticket/verify-otp' => [[['_route' => 'agent_verify_otp', '_controller' => 'App\\Controller\\Business\\AgentController::verifyOtp'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/agent/ticket/scan' => [[['_route' => 'scan_agent_ticket', '_controller' => 'App\\Controller\\Business\\AgentController::scanTicket'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/agent/scans/history' => [[['_route' => 'get_agent_scan_history', '_controller' => 'App\\Controller\\Business\\AgentController::getScanHistory'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/api/private/agent/notifications' => [[['_route' => 'get_agent_notifications', '_controller' => 'App\\Controller\\Business\\AgentController::getNotifications'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/api/private/agent/fcm-token' => [[['_route' => 'fcm_token_agent_private', '_controller' => 'App\\Controller\\Business\\AgentController::updateFcmToken'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/agent/rating' => [[['_route' => 'submit_agent_rating', '_controller' => 'App\\Controller\\Business\\AgentController::submitRating'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/agent/notifications/read' => [[['_route' => 'mark_agent_notifications_read', '_controller' => 'App\\Controller\\Business\\AgentController::markNotificationsRead'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/agent/performances' => [[['_route' => 'agent_performances_private', '_controller' => 'App\\Controller\\Business\\AgentController::getPerformances'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/api/private/company' => [
            [['_route' => 'index_company_private', '_controller' => 'App\\Controller\\Business\\CompanyController::index'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_company_private_slash', '_controller' => 'App\\Controller\\Business\\CompanyController::index'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/private/company/show' => [[['_route' => 'show_company_private', '_controller' => 'App\\Controller\\Business\\CompanyController::show'], null, ['GET' => 0], null, false, false, null]],
        '/api/private/company/new' => [[['_route' => 'new_company_private', '_controller' => 'App\\Controller\\Business\\CompanyController::new'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/company/espace/dashboard' => [[['_route' => 'company_espace_dashboard', '_controller' => 'App\\Controller\\Business\\CompanyController::espaceDashboard'], null, ['GET' => 0], null, false, false, null]],
        '/api/private/company/espace/activites-gares' => [[['_route' => 'company_espace_activites_gares', '_controller' => 'App\\Controller\\Business\\CompanyController::espaceActivitesGares'], null, ['GET' => 0], null, false, false, null]],
        '/api/private/company/espace/billets-scannes' => [[['_route' => 'company_espace_billets_scannes', '_controller' => 'App\\Controller\\Business\\CompanyController::espaceBilletsScannes'], null, ['GET' => 0], null, false, false, null]],
        '/api/private/company/espace/finances' => [[['_route' => 'company_espace_finances', '_controller' => 'App\\Controller\\Business\\CompanyController::espaceFinances'], null, ['GET' => 0], null, false, false, null]],
        '/api/private/company-fund' => [
            [['_route' => 'index_company_fund_private', '_controller' => 'App\\Controller\\Business\\CompanyFundController::index'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_company_fund_private_slash', '_controller' => 'App\\Controller\\Business\\CompanyFundController::index'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/private/company-fund/new' => [[['_route' => 'new_company_fund_private', '_controller' => 'App\\Controller\\Business\\CompanyFundController::new'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/company-invoice' => [
            [['_route' => 'index_company_invoice_private', '_controller' => 'App\\Controller\\Business\\CompanyInvoiceController::index'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_company_invoice_private_slash', '_controller' => 'App\\Controller\\Business\\CompanyInvoiceController::index'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/private/company-invoice/new' => [[['_route' => 'new_company_invoice_private', '_controller' => 'App\\Controller\\Business\\CompanyInvoiceController::new'], null, ['POST' => 0], null, false, false, null]],
        '/api/credit' => [
            [['_route' => 'index_credit_pub', '_controller' => 'App\\Controller\\Business\\CreditController::index'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_credit_pub_slash', '_controller' => 'App\\Controller\\Business\\CreditController::index'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/private/credit' => [
            [['_route' => 'index_credit', '_controller' => 'App\\Controller\\Business\\CreditController::index'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_credit_slash', '_controller' => 'App\\Controller\\Business\\CreditController::index'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/private/credit-request' => [
            [['_route' => 'index_credit_request', '_controller' => 'App\\Controller\\Business\\CreditController::index'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_credit_request_slash', '_controller' => 'App\\Controller\\Business\\CreditController::index'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/private/credit/new' => [[['_route' => 'new_credit', '_controller' => 'App\\Controller\\Business\\CreditController::new'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/credit-request/new' => [[['_route' => 'new_credit_request', '_controller' => 'App\\Controller\\Business\\CreditController::new'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/credit/scan-ticket' => [[['_route' => 'scan_ticket', '_controller' => 'App\\Controller\\Business\\CreditController::scanTicket'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/credit-request/scan-ticket' => [[['_route' => 'scan_ticket_legacy', '_controller' => 'App\\Controller\\Business\\CreditController::scanTicket'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/passenger/credit/pending' => [[['_route' => 'get_passenger_pending_credit_private', '_controller' => 'App\\Controller\\Business\\CreditController::getPendingCreditRequestPublic'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/api/private/passenger/credit-request/pending' => [[['_route' => 'get_passenger_pending_credit_request_private', '_controller' => 'App\\Controller\\Business\\CreditController::getPendingCreditRequestPublic'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/api/public/passenger/credit/pending' => [[['_route' => 'get_passenger_pending_credit_public', '_controller' => 'App\\Controller\\Business\\CreditController::getPendingCreditRequestPublic'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/api/public/passenger/credit-request/pending' => [[['_route' => 'get_passenger_pending_credit_request_public', '_controller' => 'App\\Controller\\Business\\CreditController::getPendingCreditRequestPublic'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/api/private/passenger/credit/submit' => [[['_route' => 'submit_passenger_credit_private', '_controller' => 'App\\Controller\\Business\\CreditController::submitCreditRequestPublic'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/passenger/credit-request/submit' => [[['_route' => 'submit_passenger_credit_request_private', '_controller' => 'App\\Controller\\Business\\CreditController::submitCreditRequestPublic'], null, ['POST' => 0], null, false, false, null]],
        '/api/public/passenger/credit/submit' => [[['_route' => 'submit_passenger_credit_public', '_controller' => 'App\\Controller\\Business\\CreditController::submitCreditRequestPublic'], null, ['POST' => 0], null, false, false, null]],
        '/api/public/passenger/credit-request/submit' => [[['_route' => 'submit_passenger_credit_request_public', '_controller' => 'App\\Controller\\Business\\CreditController::submitCreditRequestPublic'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/passenger/passes' => [[['_route' => 'get_passenger_passes_private', '_controller' => 'App\\Controller\\Business\\CreditController::getPassengerPassesPublic'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/api/public/passenger/passes' => [[['_route' => 'get_passenger_passes_public', '_controller' => 'App\\Controller\\Business\\CreditController::getPassengerPassesPublic'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/api/private/payment' => [
            [['_route' => 'index_payment', '_controller' => 'App\\Controller\\Business\\CreditController::listPayments'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_payment_slash', '_controller' => 'App\\Controller\\Business\\CreditController::listPayments'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/credit-policy' => [
            [['_route' => 'index_credit_policy', '_controller' => 'App\\Controller\\Business\\CreditPolicyController::show'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_credit_policy_slash', '_controller' => 'App\\Controller\\Business\\CreditPolicyController::show'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/private/credit-policy' => [
            [['_route' => 'index_credit_policy_priv', '_controller' => 'App\\Controller\\Business\\CreditPolicyController::show'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_credit_policy_priv_slash', '_controller' => 'App\\Controller\\Business\\CreditPolicyController::show'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/public/credit-policy' => [
            [['_route' => 'index_credit_policy_pub', '_controller' => 'App\\Controller\\Business\\CreditPolicyController::show'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_credit_policy_pub_slash', '_controller' => 'App\\Controller\\Business\\CreditPolicyController::show'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/credit-policy/update' => [
            [['_route' => 'update_credit_policy', '_controller' => 'App\\Controller\\Business\\CreditPolicyController::update'], null, ['POST' => 0, 'PUT' => 1], null, false, false, null],
            [['_route' => 'update_credit_policy_slash', '_controller' => 'App\\Controller\\Business\\CreditPolicyController::update'], null, ['POST' => 0, 'PUT' => 1], null, true, false, null],
        ],
        '/api/private/credit-policy/update' => [
            [['_route' => 'update_credit_policy_priv', '_controller' => 'App\\Controller\\Business\\CreditPolicyController::update'], null, ['POST' => 0, 'PUT' => 1], null, false, false, null],
            [['_route' => 'update_credit_policy_priv_slash', '_controller' => 'App\\Controller\\Business\\CreditPolicyController::update'], null, ['POST' => 0, 'PUT' => 1], null, true, false, null],
        ],
        '/api/public/credit-policy/update' => [
            [['_route' => 'update_credit_policy_pub', '_controller' => 'App\\Controller\\Business\\CreditPolicyController::update'], null, ['POST' => 0, 'PUT' => 1], null, false, false, null],
            [['_route' => 'update_credit_policy_pub_slash', '_controller' => 'App\\Controller\\Business\\CreditPolicyController::update'], null, ['POST' => 0, 'PUT' => 1], null, true, false, null],
        ],
        '/api/private/passenger' => [
            [['_route' => 'index_passenger', '_controller' => 'App\\Controller\\Business\\PassengerController::index'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_passenger_slash', '_controller' => 'App\\Controller\\Business\\PassengerController::index'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/private/passenger/new' => [[['_route' => 'new_passenger', '_controller' => 'App\\Controller\\Business\\PassengerController::new'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/passenger/identity' => [[['_route' => 'submit_passenger_identity_private', '_controller' => 'App\\Controller\\Business\\PassengerController::submitIdentity'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/passenger/dashboard' => [[['_route' => 'get_passenger_dashboard', '_controller' => 'App\\Controller\\Business\\PassengerController::getDashboardData'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/api/public/passenger/dashboard' => [[['_route' => 'get_passenger_dashboard_public', '_controller' => 'App\\Controller\\Business\\PassengerController::getDashboardData'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/api/private/passenger/notifications' => [[['_route' => 'get_passenger_notifications', '_controller' => 'App\\Controller\\Business\\PassengerController::getNotifications'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/api/private/passenger/notifications/mark-read' => [[['_route' => 'mark_notifications_read', '_controller' => 'App\\Controller\\Business\\PassengerController::markNotificationsRead'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/passenger/rating' => [[['_route' => 'submit_passenger_rating', '_controller' => 'App\\Controller\\Business\\PassengerController::submitRating'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/passenger/fcm-token' => [[['_route' => 'update_passenger_fcm_token', '_controller' => 'App\\Controller\\Business\\PassengerController::updateFcmToken'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/passenger/referentiel/config' => [[['_route' => 'passenger_referentiel_config', '_controller' => 'App\\Controller\\Business\\PassengerController::getDemandeCreditConfig'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/api/private/passenger/demande-credit-config' => [[['_route' => 'passenger_demande_credit_config', '_controller' => 'App\\Controller\\Business\\PassengerController::getDemandeCreditConfig'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/api/private/passenger/reimburse' => [[['_route' => 'reimburse_passenger_private', '_controller' => 'App\\Controller\\Business\\PassengerController::submitReimbursement'], null, ['POST' => 0], null, false, false, null]],
        '/api/public/passenger/reimburse' => [[['_route' => 'reimburse_passenger_public', '_controller' => 'App\\Controller\\Business\\PassengerController::submitReimbursement'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/passenger/credit/reimburse' => [[['_route' => 'reimburse_passenger_credit_private', '_controller' => 'App\\Controller\\Business\\PassengerController::submitReimbursement'], null, ['POST' => 0], null, false, false, null]],
        '/api/public/passenger/credit/reimburse' => [[['_route' => 'reimburse_passenger_credit_public', '_controller' => 'App\\Controller\\Business\\PassengerController::submitReimbursement'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/passenger/reimbursements' => [[['_route' => 'get_passenger_reimbursements_private', '_controller' => 'App\\Controller\\Business\\PassengerController::getReimbursements'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/api/public/passenger/reimbursements' => [[['_route' => 'get_passenger_reimbursements_public', '_controller' => 'App\\Controller\\Business\\PassengerController::getReimbursements'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/api/private/passenger/contacts/sync' => [[['_route' => 'sync_passenger_contacts_private', '_controller' => 'App\\Controller\\Business\\PassengerController::syncContacts'], null, ['POST' => 0], null, false, false, null]],
        '/api/public/passenger/contacts/sync' => [[['_route' => 'sync_passenger_contacts_public', '_controller' => 'App\\Controller\\Business\\PassengerController::syncContacts'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/passenger/reminder/send' => [[['_route' => 'send_passenger_reminder', '_controller' => 'App\\Controller\\Business\\PassengerController::sendPassengerReminder'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/passenger/reminder/send-all' => [[['_route' => 'send_all_passengers_reminders', '_controller' => 'App\\Controller\\Business\\PassengerController::sendAllPassengerReminders'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/route' => [
            [['_route' => 'index_route_private', '_controller' => 'App\\Controller\\Business\\RouteController::index'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_route_private_slash', '_controller' => 'App\\Controller\\Business\\RouteController::index'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/private/route/show' => [[['_route' => 'show_route_private', '_controller' => 'App\\Controller\\Business\\RouteController::show'], null, ['GET' => 0], null, false, false, null]],
        '/api/private/route/new' => [[['_route' => 'new_route_private', '_controller' => 'App\\Controller\\Business\\RouteController::new'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/station' => [
            [['_route' => 'index_station_private', '_controller' => 'App\\Controller\\Business\\StationController::index'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_station_private_slash', '_controller' => 'App\\Controller\\Business\\StationController::index'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/private/station/show' => [[['_route' => 'show_station_private', '_controller' => 'App\\Controller\\Business\\StationController::show'], null, ['GET' => 0], null, false, false, null]],
        '/api/private/station/new' => [[['_route' => 'new_station_private', '_controller' => 'App\\Controller\\Business\\StationController::new'], null, ['POST' => 0], null, false, false, null]],
        '/api/private/tariff' => [
            [['_route' => 'index_tariff_private', '_controller' => 'App\\Controller\\Business\\TariffController::index'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_tariff_private_slash', '_controller' => 'App\\Controller\\Business\\TariffController::index'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/private/tariff/show' => [[['_route' => 'show_tariff_private', '_controller' => 'App\\Controller\\Business\\TariffController::show'], null, ['GET' => 0], null, false, false, null]],
        '/api/private/tariff/new' => [[['_route' => 'new_tariff_private', '_controller' => 'App\\Controller\\Business\\TariffController::new'], null, ['POST' => 0], null, false, false, null]],
        '/api/ticket' => [
            [['_route' => 'index_ticket_pub', '_controller' => 'App\\Controller\\Business\\TicketController::index'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_ticket_pub_slash', '_controller' => 'App\\Controller\\Business\\TicketController::index'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/ticket/list' => [[['_route' => 'index_ticket_pub_list', '_controller' => 'App\\Controller\\Business\\TicketController::index'], null, ['GET' => 0], null, false, false, null]],
        '/api/private/ticket' => [
            [['_route' => 'index_ticket', '_controller' => 'App\\Controller\\Business\\TicketController::index'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_ticket_slash', '_controller' => 'App\\Controller\\Business\\TicketController::index'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/private/ticket/list' => [[['_route' => 'index_ticket_list', '_controller' => 'App\\Controller\\Business\\TicketController::index'], null, ['GET' => 0], null, false, false, null]],
        '/api/public/ticket' => [
            [['_route' => 'index_ticket_public', '_controller' => 'App\\Controller\\Business\\TicketController::index'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'index_ticket_public_slash', '_controller' => 'App\\Controller\\Business\\TicketController::index'], null, ['GET' => 0], null, true, false, null],
        ],
        '/api/public/ticket/list' => [[['_route' => 'index_ticket_public_list', '_controller' => 'App\\Controller\\Business\\TicketController::index'], null, ['GET' => 0], null, false, false, null]],
        '/api/private/dashboard/main' => [[['_route' => 'dashboard_main', '_controller' => 'App\\Controller\\Dashboard\\DashboardController::mainDashboard'], null, ['GET' => 0], null, false, false, null]],
        '/api/private/dashboard/admin' => [[['_route' => 'dashboard_admin', '_controller' => 'App\\Controller\\Dashboard\\DashboardController::adminDashboard'], null, ['GET' => 0], null, false, false, null]],
        '/api/private/dashboard/passe-voyage' => [[['_route' => 'dashboard_passe_voyage', '_controller' => 'App\\Controller\\Dashboard\\DashboardController::passeVoyageDashboard'], null, ['GET' => 0], null, false, false, null]],
        '/api/private/extra/settings/general' => [[['_route' => 'get_general_settings', '_controller' => 'App\\Controller\\Extra\\GeneralSettingController::getSettings'], null, ['GET' => 0], null, false, false, null]],
        '/api/private/extra/settings/general/update' => [[['_route' => 'update_general_settings', '_controller' => 'App\\Controller\\Extra\\GeneralSettingController::updateSettings'], null, ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        '/api/private/extra/settings/general/history' => [[['_route' => 'get_general_settings_history', '_controller' => 'App\\Controller\\Extra\\GeneralSettingController::getHistory'], null, ['GET' => 0], null, false, false, null]],
        '/api/private/extra/settings/notifications' => [[['_route' => 'get_extra_notification_settings', '_controller' => 'App\\Controller\\Extra\\GeneralSettingController::getNotificationSettings'], null, ['GET' => 0], null, false, false, null]],
        '/api/private/extra/settings/notifications/update' => [[['_route' => 'update_extra_notification_settings', '_controller' => 'App\\Controller\\Extra\\GeneralSettingController::updateNotificationSettings'], null, ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        '/api/public/server/ip' => [[['_route' => 'server_ip', '_controller' => 'App\\Controller\\Extra\\ServerController::getIp'], null, ['GET' => 0], null, false, false, null]],
        '/' => [[['_route' => 'app_landing', '_controller' => 'App\\Controller\\LandingController::index'], null, null, null, false, false, null]],
        '/api/public/agent/send-otp' => [[['_route' => 'send_otp_agent_public', '_controller' => 'App\\Controller\\PublicAgentController::sendOtp'], null, ['POST' => 0], null, false, false, null]],
        '/api/public/agent/verify-otp' => [[['_route' => 'verify_otp_agent_public', '_controller' => 'App\\Controller\\PublicAgentController::verifyOtp'], null, ['POST' => 0], null, false, false, null]],
        '/api/public/agent/register' => [[['_route' => 'register_agent_public', '_controller' => 'App\\Controller\\PublicAgentController::registerAgent'], null, ['POST' => 0], null, false, false, null]],
        '/api/public/agent/login' => [[['_route' => 'login_agent_public', '_controller' => 'App\\Controller\\PublicAgentController::loginAgent'], null, ['POST' => 0], null, false, false, null]],
        '/api/public/agent/fcm-token' => [[['_route' => 'fcm_token_agent_public', '_controller' => 'App\\Controller\\PublicAgentController::updateFcmToken'], null, ['POST' => 0], null, false, false, null]],
        '/api/public/referentiel/demande-credit-config' => [[['_route' => 'public_referentiel_credit_config', '_controller' => 'App\\Controller\\PublicReferentialController::getDemandeCreditConfig'], null, ['GET' => 0], null, false, false, null]],
        '/api/public/referentiel/config' => [[['_route' => 'public_referentiel_config', '_controller' => 'App\\Controller\\PublicReferentialController::getDemandeCreditConfig'], null, ['GET' => 0], null, false, false, null]],
        '/api/public/referentiel/partners' => [[['_route' => 'public_referentiel_partners', '_controller' => 'App\\Controller\\PublicReferentialController::getDemandeCreditConfig'], null, ['GET' => 0], null, false, false, null]],
        '/api/public/passenger/send-otp' => [[['_route' => 'send_otp_public', '_controller' => 'App\\Controller\\PublicRegistrationController::sendOtp'], null, ['POST' => 0], null, false, false, null]],
        '/api/public/passenger/verify-otp' => [[['_route' => 'verify_otp_public', '_controller' => 'App\\Controller\\PublicRegistrationController::verifyOtp'], null, ['POST' => 0], null, false, false, null]],
        '/api/public/passenger/register' => [[['_route' => 'register_passenger_public', '_controller' => 'App\\Controller\\PublicRegistrationController::registerPassenger'], null, ['POST' => 0], null, false, false, null]],
        '/api/public/passenger/login' => [[['_route' => 'login_passenger_public', '_controller' => 'App\\Controller\\PublicRegistrationController::loginPassenger'], null, ['POST' => 0], null, false, false, null]],
        '/api/login' => [
            [['_route' => 'login', '_controller' => 'App\\Controller\\Security\\SecurityController::login'], null, ['POST' => 0, 'GET' => 1], null, false, false, null],
            [['_route' => 'api_login_check', '_controller' => 'App\\Controller\\Security\\SecurityController::loginCheck'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/auth/me' => [[['_route' => 'auth_me', '_controller' => 'App\\Controller\\Security\\SecurityController::me'], null, ['GET' => 0], null, false, false, null]],
        '/api/logout' => [
            [['_route' => 'logout', '_controller' => 'App\\Controller\\Security\\SecurityController::logout'], null, ['POST' => 0], null, false, false, null],
            [['_route' => 'api_logout_check', '_controller' => 'App\\Controller\\Security\\SecurityController::logoutCheck'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/forgot' => [
            [['_route' => 'forgot_password', '_controller' => 'App\\Controller\\Security\\SecurityController::forgot'], null, ['POST' => 0, 'GET' => 1], null, false, false, null],
            [['_route' => 'api_forgot_check', '_controller' => 'App\\Controller\\Security\\SecurityController::forgotCheck'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/token/refresh' => [[['_route' => 'api_refresh', '_controller' => 'App\\Controller\\Security\\SecurityController::refreshTokenAction'], null, ['POST' => 0], null, false, false, null]],
        '/api/auth/edit/password' => [[['_route' => 'edit_passord', '_controller' => 'App\\Controller\\Security\\SecurityController::editPassword'], null, ['POST' => 0], null, false, false, null]],
        '/api/auth/rest/password' => [[['_route' => 'reset_passord', '_controller' => 'App\\Controller\\Security\\SecurityController::resetPassword'], null, ['POST' => 0], null, false, false, null]],
        '/api/auth/fcm-token' => [[['_route' => 'update_fcm_token', '_controller' => 'App\\Controller\\Security\\SecurityController::updateFcmToken'], null, ['POST' => 0], null, false, false, null]],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/_error/(\\d+)(?:\\.([^/]++))?(*:35)'
                .'|/api/(?'
                    .'|private/(?'
                        .'|a(?'
                            .'|dmin/(?'
                                .'|role/([^/]++)/(?'
                                    .'|edit(*:94)'
                                    .'|delete(*:107)'
                                .')'
                                .'|user/([^/]++)/(?'
                                    .'|edit(*:137)'
                                    .'|delete(*:151)'
                                    .'|toggle(*:165)'
                                .')'
                            .')'
                            .'|gent/([^/]++)/(?'
                                .'|show(*:196)'
                                .'|edit(*:208)'
                                .'|assign(*:222)'
                                .'|toggle\\-status(*:244)'
                                .'|delete(*:258)'
                            .')'
                        .')'
                        .'|c(?'
                            .'|ompany(?'
                                .'|/(?'
                                    .'|([^/]++)/(?'
                                        .'|edit(*:301)'
                                        .'|delete(*:315)'
                                        .'|toggle(*:329)'
                                    .')'
                                    .'|espace/activites\\-gares/([^/]++)/stats(*:376)'
                                .')'
                                .'|\\-(?'
                                    .'|fund/([^/]++)/(?'
                                        .'|edit(*:411)'
                                        .'|delete(*:425)'
                                        .'|show(*:437)'
                                        .'|recharge(*:453)'
                                        .'|history(*:468)'
                                    .')'
                                    .'|invoice/([^/]++)/(?'
                                        .'|toggle\\-paid(*:509)'
                                        .'|delete(*:523)'
                                    .')'
                                .')'
                            .')'
                            .'|redit(?'
                                .'|/([^/]++)/(?'
                                    .'|show(?'
                                        .'|(*:562)'
                                        .'|(*:570)'
                                    .')'
                                    .'|edit(?'
                                        .'|(*:586)'
                                        .'|(*:594)'
                                    .')'
                                    .'|approve(?'
                                        .'|(*:613)'
                                        .'|(*:621)'
                                    .')'
                                    .'|re(?'
                                        .'|ject(?'
                                            .'|(*:642)'
                                            .'|(*:650)'
                                        .')'
                                        .'|imburse(?'
                                            .'|(*:669)'
                                            .'|(*:677)'
                                        .')'
                                    .')'
                                    .'|delete(?'
                                        .'|(*:696)'
                                        .'|(*:704)'
                                    .')'
                                .')'
                                .'|\\-request/([^/]++)/(?'
                                    .'|show(?'
                                        .'|(*:743)'
                                        .'|(*:751)'
                                    .')'
                                    .'|edit(?'
                                        .'|(*:767)'
                                        .'|(*:775)'
                                    .')'
                                    .'|approve(?'
                                        .'|(*:794)'
                                        .'|(*:802)'
                                    .')'
                                    .'|re(?'
                                        .'|ject(?'
                                            .'|(*:823)'
                                            .'|(*:831)'
                                        .')'
                                        .'|imburse(?'
                                            .'|(*:850)'
                                            .'|(*:858)'
                                        .')'
                                    .')'
                                    .'|delete(?'
                                        .'|(*:877)'
                                        .'|(*:885)'
                                    .')'
                                .')'
                            .')'
                        .')'
                        .'|passenger/([^/]++)/(?'
                            .'|show(*:923)'
                            .'|edit(*:935)'
                            .'|verify\\-kyc(*:954)'
                            .'|delete(*:968)'
                            .'|toggle\\-blacklist(*:993)'
                        .')'
                        .'|route/([^/]++)/(?'
                            .'|edit(*:1024)'
                            .'|delete(*:1039)'
                            .'|toggle(*:1054)'
                        .')'
                        .'|station/([^/]++)/(?'
                            .'|edit(*:1088)'
                            .'|delete(*:1103)'
                            .'|toggle(*:1118)'
                        .')'
                        .'|tariff/([^/]++)/(?'
                            .'|edit(*:1151)'
                            .'|delete(*:1166)'
                        .')'
                    .')'
                    .'|credit/([^/]++)/(?'
                        .'|show(?'
                            .'|(*:1203)'
                            .'|(*:1212)'
                        .')'
                        .'|edit(?'
                            .'|(*:1229)'
                            .'|(*:1238)'
                        .')'
                        .'|approve(?'
                            .'|(*:1258)'
                            .'|(*:1267)'
                        .')'
                        .'|re(?'
                            .'|ject(?'
                                .'|(*:1289)'
                                .'|(*:1298)'
                            .')'
                            .'|imburse(?'
                                .'|(*:1318)'
                                .'|(*:1327)'
                            .')'
                        .')'
                        .'|delete(?'
                            .'|(*:1347)'
                            .'|(*:1356)'
                        .')'
                    .')'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        35 => [[['_route' => '_preview_error', '_controller' => 'error_controller::preview', '_format' => 'html'], ['code', '_format'], null, null, false, true, null]],
        94 => [[['_route' => 'edit_role_admin', '_controller' => 'App\\Controller\\Admin\\RoleController::edit'], ['uuid'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        107 => [[['_route' => 'delete_role_admin', '_controller' => 'App\\Controller\\Admin\\RoleController::delete'], ['uuid'], ['DELETE' => 0, 'POST' => 1], null, false, false, null]],
        137 => [[['_route' => 'edit_user_admin', '_controller' => 'App\\Controller\\Admin\\UserController::edit'], ['uuid'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        151 => [[['_route' => 'delete_user_admin', '_controller' => 'App\\Controller\\Admin\\UserController::delete'], ['uuid'], ['DELETE' => 0, 'POST' => 1], null, false, false, null]],
        165 => [[['_route' => 'toggle_user_admin', '_controller' => 'App\\Controller\\Admin\\UserController::toggle'], ['uuid'], ['PATCH' => 0, 'POST' => 1], null, false, false, null]],
        196 => [[['_route' => 'show_agent', '_controller' => 'App\\Controller\\Business\\AgentController::show'], ['uuid'], ['GET' => 0], null, false, false, null]],
        208 => [[['_route' => 'edit_agent', '_controller' => 'App\\Controller\\Business\\AgentController::edit'], ['uuid'], ['PUT' => 0, 'POST' => 1], null, false, false, null]],
        222 => [[['_route' => 'assign_agent', '_controller' => 'App\\Controller\\Business\\AgentController::assign'], ['uuid'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        244 => [[['_route' => 'toggle_status_agent', '_controller' => 'App\\Controller\\Business\\AgentController::toggleStatus'], ['uuid'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        258 => [[['_route' => 'delete_agent', '_controller' => 'App\\Controller\\Business\\AgentController::delete'], ['uuid'], ['DELETE' => 0], null, false, false, null]],
        301 => [[['_route' => 'edit_company_private', '_controller' => 'App\\Controller\\Business\\CompanyController::edit'], ['uuid'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        315 => [[['_route' => 'delete_company_private', '_controller' => 'App\\Controller\\Business\\CompanyController::delete'], ['uuid'], ['DELETE' => 0, 'POST' => 1], null, false, false, null]],
        329 => [[['_route' => 'toggle_company_private', '_controller' => 'App\\Controller\\Business\\CompanyController::toggle'], ['uuid'], ['PATCH' => 0, 'POST' => 1], null, false, false, null]],
        376 => [[['_route' => 'company_espace_activites_gares_stats', '_controller' => 'App\\Controller\\Business\\CompanyController::espaceActivitesGaresStats'], ['uuid'], ['GET' => 0], null, false, false, null]],
        411 => [[['_route' => 'edit_company_fund_private', '_controller' => 'App\\Controller\\Business\\CompanyFundController::edit'], ['uuid'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        425 => [[['_route' => 'delete_company_fund_private', '_controller' => 'App\\Controller\\Business\\CompanyFundController::delete'], ['uuid'], ['DELETE' => 0, 'POST' => 1], null, false, false, null]],
        437 => [[['_route' => 'show_company_fund_private', '_controller' => 'App\\Controller\\Business\\CompanyFundController::show'], ['uuid'], ['GET' => 0], null, false, false, null]],
        453 => [[['_route' => 'recharge_company_fund_private', '_controller' => 'App\\Controller\\Business\\CompanyFundController::recharge'], ['uuid'], ['POST' => 0], null, false, false, null]],
        468 => [[['_route' => 'history_company_fund_private', '_controller' => 'App\\Controller\\Business\\CompanyFundController::history'], ['uuid'], ['GET' => 0, 'POST' => 1], null, false, false, null]],
        509 => [[['_route' => 'toggle_paid_company_invoice_private', '_controller' => 'App\\Controller\\Business\\CompanyInvoiceController::togglePaid'], ['uuid'], ['PATCH' => 0, 'POST' => 1], null, false, false, null]],
        523 => [[['_route' => 'delete_company_invoice_private', '_controller' => 'App\\Controller\\Business\\CompanyInvoiceController::delete'], ['uuid'], ['DELETE' => 0, 'POST' => 1], null, false, false, null]],
        562 => [[['_route' => 'show_credit', 'uuid' => null, '_controller' => 'App\\Controller\\Business\\CreditController::show'], ['uuid'], ['GET' => 0], null, false, false, null]],
        570 => [[['_route' => 'show_credit_id', 'id' => null, '_controller' => 'App\\Controller\\Business\\CreditController::show'], ['id'], ['GET' => 0], null, false, false, null]],
        586 => [[['_route' => 'edit_credit', 'uuid' => null, '_controller' => 'App\\Controller\\Business\\CreditController::edit'], ['uuid'], ['PUT' => 0, 'POST' => 1], null, false, false, null]],
        594 => [[['_route' => 'edit_credit_id', 'id' => null, '_controller' => 'App\\Controller\\Business\\CreditController::edit'], ['id'], ['PUT' => 0, 'POST' => 1], null, false, false, null]],
        613 => [[['_route' => 'approve_credit', 'uuid' => null, '_controller' => 'App\\Controller\\Business\\CreditController::approve'], ['uuid'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        621 => [[['_route' => 'approve_credit_id', 'id' => null, '_controller' => 'App\\Controller\\Business\\CreditController::approve'], ['id'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        642 => [[['_route' => 'reject_credit', 'uuid' => null, '_controller' => 'App\\Controller\\Business\\CreditController::reject'], ['uuid'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        650 => [[['_route' => 'reject_credit_id', 'id' => null, '_controller' => 'App\\Controller\\Business\\CreditController::reject'], ['id'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        669 => [[['_route' => 'reimburse_credit', 'uuid' => null, '_controller' => 'App\\Controller\\Business\\CreditController::reimburse'], ['uuid'], ['POST' => 0], null, false, false, null]],
        677 => [[['_route' => 'reimburse_credit_id', 'id' => null, '_controller' => 'App\\Controller\\Business\\CreditController::reimburse'], ['id'], ['POST' => 0], null, false, false, null]],
        696 => [[['_route' => 'delete_credit', 'uuid' => null, '_controller' => 'App\\Controller\\Business\\CreditController::delete'], ['uuid'], ['DELETE' => 0], null, false, false, null]],
        704 => [[['_route' => 'delete_credit_id', 'id' => null, '_controller' => 'App\\Controller\\Business\\CreditController::delete'], ['id'], ['DELETE' => 0], null, false, false, null]],
        743 => [[['_route' => 'show_credit_request', 'uuid' => null, '_controller' => 'App\\Controller\\Business\\CreditController::show'], ['uuid'], ['GET' => 0], null, false, false, null]],
        751 => [[['_route' => 'show_credit_request_id', 'id' => null, '_controller' => 'App\\Controller\\Business\\CreditController::show'], ['id'], ['GET' => 0], null, false, false, null]],
        767 => [[['_route' => 'edit_credit_request', 'uuid' => null, '_controller' => 'App\\Controller\\Business\\CreditController::edit'], ['uuid'], ['PUT' => 0, 'POST' => 1], null, false, false, null]],
        775 => [[['_route' => 'edit_credit_request_id', 'id' => null, '_controller' => 'App\\Controller\\Business\\CreditController::edit'], ['id'], ['PUT' => 0, 'POST' => 1], null, false, false, null]],
        794 => [[['_route' => 'approve_credit_request', 'uuid' => null, '_controller' => 'App\\Controller\\Business\\CreditController::approve'], ['uuid'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        802 => [[['_route' => 'approve_credit_request_id', 'id' => null, '_controller' => 'App\\Controller\\Business\\CreditController::approve'], ['id'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        823 => [[['_route' => 'reject_credit_request', 'uuid' => null, '_controller' => 'App\\Controller\\Business\\CreditController::reject'], ['uuid'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        831 => [[['_route' => 'reject_credit_request_id', 'id' => null, '_controller' => 'App\\Controller\\Business\\CreditController::reject'], ['id'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        850 => [[['_route' => 'reimburse_credit_request', 'uuid' => null, '_controller' => 'App\\Controller\\Business\\CreditController::reimburse'], ['uuid'], ['POST' => 0], null, false, false, null]],
        858 => [[['_route' => 'reimburse_credit_request_id', 'id' => null, '_controller' => 'App\\Controller\\Business\\CreditController::reimburse'], ['id'], ['POST' => 0], null, false, false, null]],
        877 => [[['_route' => 'delete_credit_request', 'uuid' => null, '_controller' => 'App\\Controller\\Business\\CreditController::delete'], ['uuid'], ['DELETE' => 0], null, false, false, null]],
        885 => [[['_route' => 'delete_credit_request_id', 'id' => null, '_controller' => 'App\\Controller\\Business\\CreditController::delete'], ['id'], ['DELETE' => 0], null, false, false, null]],
        923 => [[['_route' => 'show_passenger', '_controller' => 'App\\Controller\\Business\\PassengerController::show'], ['uuid'], ['GET' => 0], null, false, false, null]],
        935 => [[['_route' => 'edit_passenger', '_controller' => 'App\\Controller\\Business\\PassengerController::edit'], ['uuid'], ['PUT' => 0, 'POST' => 1], null, false, false, null]],
        954 => [[['_route' => 'verify_kyc_passenger', '_controller' => 'App\\Controller\\Business\\PassengerController::verifyKyc'], ['uuid'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        968 => [[['_route' => 'delete_passenger', '_controller' => 'App\\Controller\\Business\\PassengerController::delete'], ['uuid'], ['DELETE' => 0, 'POST' => 1], null, false, false, null]],
        993 => [[['_route' => 'toggle_blacklist_passenger', '_controller' => 'App\\Controller\\Business\\PassengerController::toggleBlacklist'], ['uuid'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        1024 => [[['_route' => 'edit_route_private', '_controller' => 'App\\Controller\\Business\\RouteController::edit'], ['uuid'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        1039 => [[['_route' => 'delete_route_private', '_controller' => 'App\\Controller\\Business\\RouteController::delete'], ['uuid'], ['DELETE' => 0, 'POST' => 1], null, false, false, null]],
        1054 => [[['_route' => 'toggle_route_private', '_controller' => 'App\\Controller\\Business\\RouteController::toggle'], ['uuid'], ['PATCH' => 0, 'POST' => 1], null, false, false, null]],
        1088 => [[['_route' => 'edit_station_private', '_controller' => 'App\\Controller\\Business\\StationController::edit'], ['uuid'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        1103 => [[['_route' => 'delete_station_private', '_controller' => 'App\\Controller\\Business\\StationController::delete'], ['uuid'], ['DELETE' => 0, 'POST' => 1], null, false, false, null]],
        1118 => [[['_route' => 'toggle_station_private', '_controller' => 'App\\Controller\\Business\\StationController::toggle'], ['uuid'], ['PATCH' => 0, 'POST' => 1], null, false, false, null]],
        1151 => [[['_route' => 'edit_tariff_private', '_controller' => 'App\\Controller\\Business\\TariffController::edit'], ['uuid'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        1166 => [[['_route' => 'delete_tariff_private', '_controller' => 'App\\Controller\\Business\\TariffController::delete'], ['uuid'], ['DELETE' => 0, 'POST' => 1], null, false, false, null]],
        1203 => [[['_route' => 'show_credit_pub_uuid', 'uuid' => null, '_controller' => 'App\\Controller\\Business\\CreditController::show'], ['uuid'], ['GET' => 0], null, false, false, null]],
        1212 => [[['_route' => 'show_credit_pub_id', 'id' => null, '_controller' => 'App\\Controller\\Business\\CreditController::show'], ['id'], ['GET' => 0], null, false, false, null]],
        1229 => [[['_route' => 'edit_credit_pub_uuid', 'uuid' => null, '_controller' => 'App\\Controller\\Business\\CreditController::edit'], ['uuid'], ['PUT' => 0, 'POST' => 1], null, false, false, null]],
        1238 => [[['_route' => 'edit_credit_pub_id', 'id' => null, '_controller' => 'App\\Controller\\Business\\CreditController::edit'], ['id'], ['PUT' => 0, 'POST' => 1], null, false, false, null]],
        1258 => [[['_route' => 'approve_credit_pub', 'uuid' => null, '_controller' => 'App\\Controller\\Business\\CreditController::approve'], ['uuid'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        1267 => [[['_route' => 'approve_credit_pub_id', 'id' => null, '_controller' => 'App\\Controller\\Business\\CreditController::approve'], ['id'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        1289 => [[['_route' => 'reject_credit_pub', 'uuid' => null, '_controller' => 'App\\Controller\\Business\\CreditController::reject'], ['uuid'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        1298 => [[['_route' => 'reject_credit_pub_id', 'id' => null, '_controller' => 'App\\Controller\\Business\\CreditController::reject'], ['id'], ['POST' => 0, 'PUT' => 1], null, false, false, null]],
        1318 => [[['_route' => 'reimburse_credit_pub_uuid', 'uuid' => null, '_controller' => 'App\\Controller\\Business\\CreditController::reimburse'], ['uuid'], ['POST' => 0], null, false, false, null]],
        1327 => [[['_route' => 'reimburse_credit_pub_id', 'id' => null, '_controller' => 'App\\Controller\\Business\\CreditController::reimburse'], ['id'], ['POST' => 0], null, false, false, null]],
        1347 => [[['_route' => 'delete_credit_pub_uuid', 'uuid' => null, '_controller' => 'App\\Controller\\Business\\CreditController::delete'], ['uuid'], ['DELETE' => 0], null, false, false, null]],
        1356 => [
            [['_route' => 'delete_credit_pub_id', 'id' => null, '_controller' => 'App\\Controller\\Business\\CreditController::delete'], ['id'], ['DELETE' => 0], null, false, false, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
