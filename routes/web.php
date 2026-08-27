<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — CRM only (extracted)
|--------------------------------------------------------------------------
*/

// Root → CRM login (this app is CRM-only).
Route::get('/', 'UtilityController@home')->name('home');

// Customer Portal (no auth - token based)
// Customer Portal (no auth - token based)
Route::get('portal/login', 'CustomerPortalController@loginPage')->name('portal.login');
Route::post('portal/login', 'CustomerPortalController@doLogin')->name('portal.do_login');
Route::get('portal/logout', 'CustomerPortalController@logout')->name('portal.logout');
Route::get('portal/track/{orderId}', 'CustomerPortalController@track')->name('portal.track');
Route::post('portal/track/{orderId}/message', 'CustomerPortalController@sendMessage')->name('portal.send_message');
Route::get('portal/track/{orderId}/messages', 'CustomerPortalController@getMessages')->name('portal.get_messages');

Route::get('brand-assets/{filename}', 'UtilityController@brandAsset')
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('brand.assets.show');

// CRM attachments may live inside the Laravel public directory while some
// deployments expose public_html as the web root. This authenticated fallback
// keeps existing message attachments accessible in either layout.
Route::get('crm_attachments/{inquiry}/{filename}', 'Crm\EmailController@attachment')
    ->middleware(['auth:crm', 'crm.ip'])
    ->where(['inquiry' => '[0-9]+', 'filename' => '[^/]+'])
    ->name('crm.attachments.show');

// CRM Routes
// CRM Routes
Route::group(['prefix' => 'crm', 'namespace' => 'Crm'], function () {
    // Auth
    Route::get('login', 'AuthController@showLoginForm')->name('crm.login');
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout')->name('crm.logout');

    Route::group(['middleware' => ['auth:crm', 'crm.ip']], function () {
        Route::get('select-workspace', 'WorkspaceController@index')->name('crm.workspaces.select');
        Route::post('select-workspace/{workspace}', 'WorkspaceController@select')->name('crm.workspaces.activate');

        Route::group(['middleware' => 'crm.workspace'], function () {
        Route::get('dashboard', 'DashboardController@index')->name('crm.dashboard');

        Route::get('get-estimates', 'EstimateTicketController@index')->name('crm.estimate_tickets.index');
        Route::get('get-estimates/create', 'EstimateTicketController@create')->name('crm.estimate_tickets.create');
        Route::post('get-estimates', 'EstimateTicketController@store')->name('crm.estimate_tickets.store');
        Route::get('get-estimates/estimation-rates', 'EstimateTicketController@indexRateMatrix')->name('crm.estimation_rates.index');
        Route::post('get-estimates/estimation-rates', 'EstimateTicketController@storeRateMatrix')->name('crm.estimation_rates.store');
        Route::post('get-estimates/estimation-rates/bulk-convert', 'EstimateTicketController@bulkConvertRateMatrix')->name('crm.estimation_rates.bulk_convert');
        Route::post('get-estimates/estimation-rates/bulk-convert-unit', 'EstimateTicketController@bulkConvertUnitMatrix')->name('crm.estimation_rates.bulk_convert_unit');
        Route::put('get-estimates/estimation-rates/{id}', 'EstimateTicketController@updateRateMatrix')->name('crm.estimation_rates.update');
        Route::delete('get-estimates/estimation-rates/{id}', 'EstimateTicketController@destroyRateMatrix')->name('crm.estimation_rates.destroy');
        Route::get('get-estimates/{id}', 'EstimateTicketController@show')->name('crm.estimate_tickets.show');
        Route::get('get-estimates/{id}/edit', 'EstimateTicketController@edit')->name('crm.estimate_tickets.edit');
        Route::post('get-estimates/{id}/update', 'EstimateTicketController@update')->name('crm.estimate_tickets.update');
        Route::post('get-estimates/{id}/claim', 'EstimateTicketController@claim')->name('crm.estimate_tickets.claim');
        Route::post('get-estimates/{id}/submit', 'EstimateTicketController@submit')->name('crm.estimate_tickets.submit');
        Route::post('get-estimates/{id}/team-lead-offer', 'EstimateTicketController@submitTeamLeadOffer')->name('crm.estimate_tickets.team_lead_offer');
        Route::post('get-estimates/{id}/owner-approve', 'EstimateTicketController@ownerApprove')->name('crm.estimate_tickets.owner_approve');
        Route::post('get-estimates/{id}/revision', 'EstimateTicketController@revision')->name('crm.estimate_tickets.revision');
        Route::post('get-estimates/{id}/return', 'EstimateTicketController@returnTicket')->name('crm.estimate_tickets.return');
        Route::post('get-estimates/{id}/complete', 'EstimateTicketController@complete')->name('crm.estimate_tickets.complete');
        Route::post('get-estimates/{id}/send-chat', 'EstimateTicketController@sendToChat')->name('crm.estimate_tickets.send_chat');
        Route::get('get-estimates/{id}/draft-pdf', 'EstimateTicketController@draftPdf')->name('crm.estimate_tickets.draft_pdf');
        Route::post('get-estimates/{id}/export', 'EstimateTicketController@exportInternalPdf')->name('crm.estimate_tickets.export');

        // My Box customer-app workflow (not shared with other CRM workspaces)
        Route::group(['middleware' => 'crm.workspace.slug:my-box-printing'], function () {
        // App Projects
        Route::get('app-projects', 'CustomProjectController@index')->name('crm.app_projects');
        Route::get('app-projects/{id}', 'CustomProjectController@show')->name('crm.app_projects.show');
        Route::delete('app-projects/{id}', 'CustomProjectController@destroy')->name('crm.app_projects.destroy');

        // Dieline Management in CRM
        Route::post('app-projects/{id}/dieline', 'CustomProjectController@uploadDieline')->name('crm.app_projects.upload_dieline');
        Route::post('dielines/{id}/fulfill', 'CustomProjectController@fulfillDielineRequest')->name('crm.dielines.fulfill');
        Route::patch('dielines/{id}/status', 'CustomProjectController@updateDielineStatus')->name('crm.dielines.update_status');
        Route::patch('dielines/{id}/rename', 'CustomProjectController@renameDieline')->name('crm.dielines.rename');
        Route::delete('dielines/{id}', 'CustomProjectController@destroyDieline')->name('crm.dielines.destroy');

        // Mockup Management in CRM
        Route::post('dielines/{dielineId}/mockup', 'CustomProjectController@uploadMockup')->name('crm.dielines.upload_mockup');
        Route::post('mockups/{id}/fulfill', 'CustomProjectController@fulfillMockupRequest')->name('crm.mockups.fulfill');
        Route::patch('mockups/{id}/status', 'CustomProjectController@updateMockupStatus')->name('crm.mockups.update_status');
        Route::delete('mockups/{id}', 'CustomProjectController@destroyMockup')->name('crm.mockups.destroy');

        // Sample Requests
        Route::get('samples', 'SampleRequestController@index')->name('crm.samples.index');
        Route::get('samples/{id}', 'SampleRequestController@show')->name('crm.samples.show');
        Route::patch('samples/{id}/status', 'SampleRequestController@updateStatus')->name('crm.samples.update_status');
        Route::patch('samples/{id}/pricing', 'SampleRequestController@updatePricing')->name('crm.samples.update_pricing');
        Route::delete('samples/{id}', 'SampleRequestController@destroy')->name('crm.samples.destroy');

        // Production Orders
        Route::patch('production-orders/{id}/status', 'CustomProjectController@updateProductionStatus')->name('crm.production_orders.update_status');
        Route::patch('production-orders/{id}/pricing', 'CustomProjectController@updateProductionPricing')->name('crm.production_orders.update_pricing');
        Route::delete('production-orders/{id}', 'CustomProjectController@destroyProductionOrder')->name('crm.production_orders.destroy');
        });

        // Al Massa designer jobs (not shared with other CRM workspaces)
        Route::group(['middleware' => 'crm.workspace.slug:mybox-packaging-app'], function () {
        Route::get('design-jobs', 'DesignJobController@index')->name('crm.design_jobs.index');
        Route::get('design-jobs/create', 'DesignJobController@create')->name('crm.design_jobs.create');
        Route::post('design-jobs', 'DesignJobController@store')->name('crm.design_jobs.store');
        Route::get('design-jobs/{id}', 'DesignJobController@show')->name('crm.design_jobs.show')->where('id', '[0-9]+');
        Route::post('design-jobs/{id}/status', 'DesignJobController@updateStatus')->name('crm.design_jobs.status');
        });

        // Emails
        Route::get('inbox', 'EmailController@index')->name('crm.emails.index');
        Route::get('inquiries', 'EmailController@inquiriesIndex')->name('crm.inquiries.index');
        Route::get('inbox/inquiry/{id}/edit', 'EmailController@editInquiry')->name('crm.emails.edit_inquiry');
        Route::post('inbox/inquiry/{id}/update', 'EmailController@updateInquiry')->name('crm.emails.update_inquiry');
        Route::post('inbox/inquiry/{id}/offer-price', 'EmailController@updateOfferPrice')->name('crm.emails.update_offer_price');
        Route::get('inbox/add-inquiry', 'EmailController@createInquiryForm')->name('crm.emails.create_form');
        Route::post('inbox/finishing-options', 'EmailController@storeFinishingOption')->name('crm.emails.finishing_options.store');
        Route::post('inbox/create', 'EmailController@createInquiry')->name('crm.emails.create_manual');
        Route::get('spam', 'EmailController@spam')->name('crm.emails.spam');
        Route::get('rejected-leads', 'EmailController@rejected')->name('crm.emails.rejected');
        Route::get('email/assignable-users', 'EmailController@getAssignableUsers')->name('crm.emails.assignable_users');
        Route::post('emails/bulk-assign', 'EmailController@bulkAssign')->name('crm.emails.bulk_assign');
        Route::get('email/{id}', 'EmailController@show')->name('crm.emails.show');
        Route::post('email/{id}/spam', 'EmailController@markAsSpam')->name('crm.emails.markSpam');
        Route::post('email/{id}/reject', 'EmailController@markAsRejected')->name('crm.emails.markRejected');
        Route::post('email/{id}/restore-rejected', 'EmailController@restoreRejected')->name('crm.emails.restoreRejected');
        Route::post('email/{id}/valid', 'EmailController@markAsValid')->name('crm.emails.markValid');
        Route::post('email/{id}/forward', 'EmailController@forward')->name('crm.emails.forward');
        Route::post('email/{id}/message', 'EmailController@sendMessage')->name('crm.messages.send');
        Route::get('email/{id}/messages', 'EmailController@getMessages')->name('crm.messages.fetch');
        Route::post('email/{id}/assign', 'EmailController@assign')->name('crm.emails.assign');
        Route::get('email/{id}/assignment-logs', 'EmailController@assignmentLogs')->name('crm.emails.assignment_logs');
        Route::post('email/{id}/note', 'EmailController@storeNote')->name('crm.emails.store_note');
        Route::post('email/{id}/calculate-cost', 'EmailController@calculateCost')->name('crm.emails.calculate_cost');
        Route::post('email/{id}/retention-update', 'EmailController@updateRetentionLog')->name('crm.emails.retention_update');
        Route::post('email/{id}/proof-revision', 'EmailController@uploadProofRevision')->name('crm.emails.upload_proof');
        Route::post('proof-revisions/{id}/status', 'EmailController@updateProofStatus')->name('crm.proof_revisions.update_status');
        Route::post('email/{id}/qc', 'EmailController@submitQualityControl')->name('crm.emails.submit_qc');
        Route::post('email/{id}/production-status', 'EmailController@updateProductionStatus')->name('crm.emails.update_production_status');

        // Chats (Live Center)
        Route::get('chats', 'ChatController@index')->name('crm.chats.index');
        Route::get('chat-list', 'ChatController@chatList')->name('crm.chats.list');
        Route::get('chat-sync', 'ChatController@syncInbox')->name('crm.chats.sync');

        // User Management (Admin Only)
        Route::post('email/{id}/status', 'EmailController@updateStatus')->name('crm.emails.status');
        Route::post('email/{id}/update-product-name', 'EmailController@updateProductName')->name('crm.emails.update_product_name');
        Route::post('email/{id}/update-product-specs', 'EmailController@updateProductSpecs')->name('crm.emails.update_product_specs');
        Route::post('email/{id}/mark-qualified', 'EmailController@markQualified')->name('crm.emails.markQualified');
        Route::delete('email/{id}', 'EmailController@destroy')->name('crm.emails.destroy');
        Route::post('email/{id}/find-social', 'EmailController@findSocialProfiles')->name('crm.emails.findSocial');
        Route::post('email/{id}/request-estimate', 'EmailController@requestEstimate')->name('crm.emails.request_estimate');
        Route::post('email/{id}/submit-estimate', 'EmailController@submitEstimate')->name('crm.emails.submit_estimate');
        Route::post('email/{id}/approve-estimate', 'EmailController@approveEstimate')->name('crm.emails.approve_estimate');
        Route::post('email/{id}/reject-estimate', 'EmailController@rejectEstimate')->name('crm.emails.reject_estimate');

        // Routes accessible by both Admin and Sales Manager
        Route::group(['middleware' => 'crm.admin_manager'], function () {
            Route::post('users/test-connection', 'UserManagementController@testConnection')->name('crm.users.test_connection');
            Route::resource('users', 'UserManagementController', ['as' => 'crm'])->except(['show']);
        });

        // Admin Only Routes
        Route::group(['middleware' => 'crm.admin'], function () {
            Route::get('logs', 'StatusLogController@index')->name('crm.logs.index');
            Route::get('team-performance', 'TeamController@index')->name('crm.team_performance');
            Route::get('team-performance/export', 'TeamController@export')->name('crm.team_performance.export');
            Route::get('reports', 'ReportController@index')->name('crm.reports.index');
            Route::get('reports/export', 'ReportController@export')->name('crm.reports.export');
            Route::get('reports/export-pdf', 'ReportController@exportPdf')->name('crm.reports.export_pdf');

            // Orders bulk invoice (Admin Only)
            Route::get('orders/bulk-invoice', 'OrdersController@bulkInvoice')->name('crm.orders.bulk_invoice');
        });

        // Orders (accessible by all authenticated CRM users: Admins, Sales Managers, and Sales Agents)
        Route::get('orders', 'OrdersController@index')->name('crm.orders.index');
        Route::get('orders/export', 'OrdersController@export')->name('crm.orders.export');
        Route::get('orders/create', 'OrdersController@create')->name('crm.orders.create');
        Route::post('orders', 'OrdersController@store')->name('crm.orders.store');
        Route::get('leads', 'LeadsController@index')->name('crm.leads.index');
        Route::get('orders/{id}/invoice', 'OrdersController@invoice')->name('crm.orders.invoice');
        Route::get('orders/{id}/invoice/edit', 'OrdersController@editInvoice')->name('crm.orders.invoice.edit');
        Route::post('orders/{id}/invoice/update', 'OrdersController@updateInvoice')->name('crm.orders.invoice.update');
        Route::post('orders/{id}/invoice/send', 'OrdersController@sendInvoice')->name('crm.orders.invoice.send');
        Route::delete('orders/{id}', 'OrdersController@destroyInvoice')->name('crm.orders.destroy');
        Route::get('deletion-logs', 'DeletionLogController@index')->name('crm.deletion_logs.index');

        // Vendor packaging purchases (Admin, Sales Manager, and Accounts)
        Route::get('general-ledger', 'GeneralLedgerController@index')->name('crm.general_ledger.index');
        Route::get('general-ledger/export', 'GeneralLedgerController@export')->name('crm.general_ledger.export');
        Route::get('vendor-purchases', 'VendorPurchaseController@index')->name('crm.vendor_purchases.index');
        Route::get('vendor-purchases/jobs', 'VendorPurchaseController@jobs')->name('crm.vendor_purchases.jobs');
        Route::get('vendor-purchases/create', 'VendorPurchaseController@create')->name('crm.vendor_purchases.create');
        Route::post('vendor-purchases/extract-invoice', 'VendorPurchaseController@extractInvoice')->name('crm.vendor_purchases.extract_invoice');
        Route::post('vendor-purchases/export', 'VendorPurchaseController@export')->name('crm.vendor_purchases.export');
        Route::post('vendor-purchases', 'VendorPurchaseController@store')->name('crm.vendor_purchases.store');
        Route::get('vendor-purchases/{id}/edit', 'VendorPurchaseController@edit')->name('crm.vendor_purchases.edit');
        Route::post('vendors', 'VendorPurchaseController@storeVendor')->name('crm.vendors.store');
        Route::put('vendor-purchases/{id}', 'VendorPurchaseController@update')->name('crm.vendor_purchases.update');
        Route::delete('vendor-purchases/{id}', 'VendorPurchaseController@destroy')->name('crm.vendor_purchases.destroy');
        Route::delete('vendors/{id}', 'VendorPurchaseController@destroyVendor')->name('crm.vendors.destroy');
        Route::patch('vendor-purchases/{id}/payment', 'VendorPurchaseController@updatePayment')->name('crm.vendor_purchases.update_payment');

        // Customer sales add-on
        Route::get('customer-sales', 'CustomerSaleController@index')->name('crm.customer_sales.index');
        Route::post('customers', 'CustomerSaleController@storeCustomer')->name('crm.customers.store');
        Route::post('customer-sales', 'CustomerSaleController@storeSale')->name('crm.customer_sales.store');
        Route::put('customer-sales/{id}', 'CustomerSaleController@updateSale')->name('crm.customer_sales.update');
        Route::patch('customer-sales/{id}/payment', 'CustomerSaleController@updatePayment')->name('crm.customer_sales.update_payment');

        // Sales Orders (Sales Workflow)
        Route::get('sales-orders', 'SalesOrderController@index')->name('crm.sales_orders.index');
        Route::post('sales-orders', 'SalesOrderController@store')->name('crm.sales_orders.store');
        Route::patch('sales-orders/{id}/payment-status', 'SalesOrderController@updatePaymentStatus')->name('crm.sales_orders.update_payment_status');
        Route::post('sales-orders/{id}/upload-artwork', 'SalesOrderController@uploadArtwork')->name('crm.sales_orders.upload_artwork');
        Route::post('sales-orders/{id}/approve-proof', 'SalesOrderController@approveProof')->name('crm.sales_orders.approve_proof');
        Route::post('sales-orders/{id}/reject-proof', 'SalesOrderController@rejectProof')->name('crm.sales_orders.reject_proof');

        // Design Department
        Route::get('design-tickets', 'DesignTicketController@index')->name('crm.design_tickets.index');
        Route::post('design-requirements/{id}/claim', 'DesignTicketController@claimRequirement')->name('crm.design_requirements.claim');
        Route::post('design-requirements/{id}/complete', 'DesignTicketController@completeRequirement')->name('crm.design_requirements.complete');
        Route::post('design-requirements/{id}/release', 'DesignTicketController@releaseRequirement')->name('crm.design_requirements.release');
        Route::post('design-requirements/{id}/return-to-sales', 'DesignTicketController@returnToSales')->name('crm.design_requirements.return_to_sales');
        Route::post('design-tickets/{id}/upload-proof', 'DesignTicketController@uploadProof')->name('crm.design_tickets.upload_proof');

        // Prepress Department
        Route::get('prepress-tickets', 'PrepressTicketController@index')->name('crm.prepress_tickets.index');
        Route::post('prepress-tickets/{id}/create-plate', 'PrepressTicketController@createPlate')->name('crm.prepress_tickets.create_plate');
        Route::post('prepress-tickets/{id}/complete', 'PrepressTicketController@complete')->name('crm.prepress_tickets.complete');
        Route::post('prepress-tickets/{id}/send-back', 'PrepressTicketController@sendBack')->name('crm.prepress_tickets.send_back');

        // Production Department
        Route::get('production-jobs', 'ProductionJobController@index')->name('crm.production_jobs.index');
        Route::get('press-tickets', 'ProductionJobController@pressTickets')->name('crm.press_tickets.index');
        Route::get('qc-tickets', 'ProductionJobController@qcTickets')->name('crm.qc_tickets.index');
        Route::get('supervisor-tickets', 'ProductionJobController@supervisorTickets')->name('crm.supervisor_tickets.index');
        Route::get('production-jobs/{id}', 'ProductionJobController@show')->name('crm.production_jobs.show');
        Route::post('production-machines', 'ProductionJobController@storeMachine')->name('crm.production_machines.store');
        Route::post('production-jobs/{id}/plan', 'ProductionJobController@plan')->name('crm.production_jobs.plan');
        Route::post('production-jobs/{id}/start-setup', 'ProductionJobController@startSetup')->name('crm.production_jobs.start_setup');
        Route::post('production-jobs/{id}/first-sheet', 'ProductionJobController@submitFirstSheet')->name('crm.production_jobs.first_sheet');
        Route::post('production-jobs/{id}/first-sheet-review', 'ProductionJobController@reviewFirstSheet')->name('crm.production_jobs.first_sheet_review');
        Route::post('production-jobs/{id}/sales-agent-review', 'ProductionJobController@salesAgentReview')->name('crm.production_jobs.sales_agent_review');
        Route::post('production-jobs/{id}/start-run', 'ProductionJobController@startRun')->name('crm.production_jobs.start_run');
        Route::post('production-jobs/{id}/complete-run', 'ProductionJobController@completeRun')->name('crm.production_jobs.complete_run');
        Route::post('production-jobs/{id}/supervisor-stage', 'ProductionJobController@updateSupervisorStage')->name('crm.production_jobs.supervisor_stage');
        Route::post('production-jobs/{id}/final-qc', 'ProductionJobController@finalQualityControl')->name('crm.production_jobs.final_qc');

        // Warehouse / Accounts / Shipping / Retention Fulfillment
        Route::get('warehouse-tickets', 'FulfillmentController@warehouseTickets')->name('crm.warehouse_tickets.index');
        Route::get('accounts-tickets', 'FulfillmentController@accountsTickets')->name('crm.accounts_tickets.index');
        Route::get('shipping-tickets', 'FulfillmentController@shippingTickets')->name('crm.shipping_tickets.index');
        Route::get('retention-tickets', 'FulfillmentController@retentionTickets')->name('crm.retention_tickets.index');
        Route::post('fulfillment/{id}/stage', 'FulfillmentController@updateStage')->name('crm.fulfillment.update');

        // Customer Portal Chat (agent side)
        Route::post('customer-chat/{orderId}/reply', '\App\Http\Controllers\CustomerPortalController@agentReply')->name('crm.customer_chat.reply');
        Route::get('customer-chat/{orderId}/messages', '\App\Http\Controllers\CustomerPortalController@getOrderChatMessages')->name('crm.customer_chat.messages');

        // Change Password (all users)
        Route::get('change-password', 'AuthController@showChangePassword')->name('crm.change_password');
        Route::post('change-password', 'AuthController@updatePassword')->name('crm.change_password.update');

        // Internal Agent Chat
        Route::get('team-chat', 'InternalChatController@index')->name('crm.team_chat.index');
        Route::get('internal-chat/agents', 'InternalChatController@getAgents')->name('crm.internal_chat.agents');
        Route::get('internal-chat/unread-total', 'InternalChatController@getUnreadTotal')->name('crm.internal_chat.unread_total');
        Route::get('internal-chat/messages/{agentId}', 'InternalChatController@getMessages')->name('crm.internal_chat.messages');
        Route::post('internal-chat/send', 'InternalChatController@sendMessage')->name('crm.internal_chat.send');
        Route::post('internal-chat/forward', 'InternalChatController@forwardMessage')->name('crm.internal_chat.forward');
        Route::post('internal-chat/delete/{id}', 'InternalChatController@deleteMessage')->name('crm.internal_chat.delete');
        Route::post('internal-chat/edit/{id}', 'InternalChatController@editMessage')->name('crm.internal_chat.edit');
        Route::post('internal-chat/ping', 'InternalChatController@ping')->name('crm.internal_chat.ping');
        });
    });
});

// Cache maintenance
Route::get('/clear-cache',  'UtilityController@cacheClear');
Route::get('/route-cache',  'UtilityController@routeCache');
Route::get('/config-cache', 'UtilityController@configCache');
Route::get('/view-clear',   'UtilityController@viewClear');

