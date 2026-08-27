<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — CRM only
|--------------------------------------------------------------------------
| Add authenticated CRM API routes here as needed. All public-site
| endpoints have been removed to keep the extracted app minimal.
*/
Route::post('crm/leads', 'Api\CrmLeadController@store')
    ->middleware('crm.lead.api');
