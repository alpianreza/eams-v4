<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

/**
 * Application controller base.
 *
 * Extending Laravel's routing controller restores controller middleware
 * support used by the administration and checklist-master modules.
 */
abstract class Controller extends BaseController
{
    //
}
