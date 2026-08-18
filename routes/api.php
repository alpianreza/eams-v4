<?php

use App\Http\Controllers\Api\AgentApiController;
use Illuminate\Support\Facades\Route;

/*
| Agent API (BR-35/36) — contract-compatible with the EAMS legacy Windows agent.
| Stateless (no session/CSRF); device authenticates via device_token.
*/
Route::match(['get', 'post'], 'agent/heartbeat', [AgentApiController::class, 'heartbeat']);
Route::match(['get', 'post'], 'agent/command', [AgentApiController::class, 'command']);
Route::match(['get', 'post'], 'agent/update', [AgentApiController::class, 'update']);
