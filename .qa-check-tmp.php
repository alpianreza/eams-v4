<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = App\Models\User::where('role', 'admin')->where('permission', 'write')->first() ?? App\Models\User::first();
Illuminate\Support\Facades\Auth::login($user);
$inv = App\Models\ComplianceInventory::with('itemType')->first();
if (!$inv) { echo "NO INVENTORY\n"; exit; }
$r = Illuminate\Support\Facades\Route::dispatchToRoute(Illuminate\Http\Request::create('/compliance/checklist/'.$inv->id.'/fill', 'GET'));
fwrite(STDERR, 'STATUS: '.$r->getStatusCode()."\n");
$html = $r->getContent();
fwrite(STDERR, 'has form: '.(str_contains($html, 'enctype="multipart/form-data"') ? 'yes' : 'no')."\n");
fwrite(STDERR, 'has x-data checklistForm: '.(str_contains($html, 'checklistForm(') ? 'yes' : 'no')."\n");
fwrite(STDERR, 'has data-eams-page: '.(str_contains($html, 'data-eams-page="checklist-fill"') ? 'yes' : 'no')."\n");
