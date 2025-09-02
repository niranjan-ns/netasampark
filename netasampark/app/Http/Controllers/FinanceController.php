<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FinanceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Finance index']);
    }

    public function expenses(): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Expenses list']);
    }

    public function storeExpense(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Expense stored']);
    }

    public function reports(): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Finance reports']);
    }
}
