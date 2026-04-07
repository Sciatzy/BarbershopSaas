<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
class NotificationController extends Controller
{
    public function index() { return view('customer.dashboard')->with('success', 'No new notifications'); }
}