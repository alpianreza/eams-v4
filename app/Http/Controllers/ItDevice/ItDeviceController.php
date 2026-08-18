<?php

namespace App\Http\Controllers\ItDevice;

use App\Http\Controllers\Controller;
use App\Models\ItDevice;
use Illuminate\View\View;

class ItDeviceController extends Controller
{
    public function index(): View
    {
        return view('it.devices.index', ['devices' => ItDevice::latest('last_seen')->paginate(20)]);
    }
}
