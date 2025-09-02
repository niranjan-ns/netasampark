<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SupportController extends Controller
{
    public function tickets(): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Tickets list']);
    }

    public function storeTicket(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Ticket created']);
    }

    public function showTicket($ticket): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Ticket details']);
    }

    public function updateTicket(Request $request, $ticket): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Ticket updated']);
    }

    public function knowledgeBase(): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Knowledge base']);
    }
}
