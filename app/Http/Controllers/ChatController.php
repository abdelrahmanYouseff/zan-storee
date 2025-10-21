<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * Get sessions data
     */
    private function getSessionsData()
    {
        return ChatMessage::select('session_id', 'customer_email')
            ->selectRaw('MAX(created_at) as last_message_time')
            ->selectRaw('COUNT(*) as message_count')
            ->selectRaw('SUM(CASE WHEN sender_type = "customer" AND is_read = 0 THEN 1 ELSE 0 END) as unread_count')
            ->groupBy('session_id', 'customer_email')
            ->orderBy('last_message_time', 'desc')
            ->get()
            ->map(function ($session) {
                $lastMessage = ChatMessage::where('session_id', $session->session_id)
                    ->latest()
                    ->first();

                return [
                    'session_id' => $session->session_id,
                    'customer_email' => $session->customer_email ?? 'Anonymous',
                    'last_message' => $lastMessage ? $lastMessage->message : '',
                    'last_message_time' => $session->last_message_time,
                    'message_count' => $session->message_count,
                    'unread_count' => $session->unread_count,
                ];
            });
    }

    /**
     * Display all chat sessions
     */
    public function index()
    {
        $sessions = $this->getSessionsData();

        return Inertia::render('Chat/Index', [
            'sessions' => $sessions,
        ]);
    }

    /**
     * Get sessions list (API endpoint for real-time updates)
     */
    public function getSessions()
    {
        $sessions = $this->getSessionsData();

        return response()->json([
            'success' => true,
            'sessions' => $sessions,
        ]);
    }

    /**
     * Get messages for a specific session
     */
    public function getMessages($sessionId)
    {
        $messages = ChatMessage::where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark customer messages as read
        ChatMessage::where('session_id', $sessionId)
            ->where('sender_type', 'customer')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Send a message from admin
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'message' => 'required|string',
        ]);

        $message = ChatMessage::create([
            'session_id' => $request->session_id,
            'customer_email' => ChatMessage::where('session_id', $request->session_id)
                ->first()
                ->customer_email ?? null,
            'sender_type' => 'admin',
            'message' => $request->message,
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Store a message from customer (API endpoint for frontend)
     */
    public function storeCustomerMessage(Request $request)
    {
        $request->validate([
            'session_id' => 'nullable|string',
            'message' => 'required|string',
            'customer_email' => 'nullable|email',
        ]);

        // Generate session_id if not provided
        $sessionId = $request->session_id ?? 'session_' . uniqid() . '_' . time();

        $message = ChatMessage::create([
            'session_id' => $sessionId,
            'customer_email' => $request->customer_email,
            'sender_type' => 'customer',
            'message' => $request->message,
            'is_read' => false,
        ]);

        // Check if there's an admin response for this session
        $adminResponse = $this->checkForAdminResponse($sessionId);

        return response()->json([
            'success' => true,
            'session_id' => $sessionId,
            'message' => $message,
            'admin_response' => $adminResponse,
        ]);
    }

    /**
     * Get admin responses for customer
     */
    public function getAdminResponses($sessionId)
    {
        // Get unread admin messages
        $messages = ChatMessage::where('session_id', $sessionId)
            ->where('sender_type', 'admin')
            ->where('is_read', false)
            ->orderBy('created_at', 'asc')
            ->get();

        // Only mark as read if there are messages
        if ($messages->isNotEmpty()) {
            // Mark these specific messages as read
            $messageIds = $messages->pluck('id')->toArray();
            ChatMessage::whereIn('id', $messageIds)
                ->update(['is_read' => true]);
        }

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Check for admin response
     */
    private function checkForAdminResponse($sessionId)
    {
        return ChatMessage::where('session_id', $sessionId)
            ->where('sender_type', 'admin')
            ->where('is_read', false)
            ->latest()
            ->first();
    }

    /**
     * Get unread count
     */
    public function getUnreadCount()
    {
        $unreadCount = ChatMessage::where('sender_type', 'customer')
            ->where('is_read', false)
            ->distinct('session_id')
            ->count('DISTINCT session_id');

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Check admin status for a session
     */
    public function getAdminStatus($sessionId)
    {
        // Check if there are any admin messages in the last 5 minutes
        $recentAdminMessages = ChatMessage::where('session_id', $sessionId)
            ->where('sender_type', 'admin')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        // Generate a random staff name
        $staffNames = [
            'Sarah Johnson', 'Michael Chen', 'Emily Rodriguez', 'David Kim', 'Lisa Thompson',
            'James Wilson', 'Maria Garcia', 'Robert Brown', 'Jennifer Lee', 'Christopher Davis',
            'Amanda Taylor', 'Daniel Martinez', 'Jessica White', 'Matthew Anderson', 'Ashley Thomas',
            'Ryan Jackson', 'Nicole Harris', 'Kevin Clark', 'Stephanie Lewis', 'Brandon Walker'
        ];

        return response()->json([
            'success' => true,
            'admin_online' => $recentAdminMessages,
            'admin_name' => $recentAdminMessages ? $staffNames[array_rand($staffNames)] : null,
        ]);
    }

    /**
     * Delete a chat session
     */
    public function deleteSession($sessionId)
    {
        try {
            ChatMessage::where('session_id', $sessionId)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Chat session deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting chat session'
            ], 500);
        }
    }
}
