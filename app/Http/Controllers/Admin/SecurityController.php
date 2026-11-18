<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class SecurityController extends Controller
{
    /**
     * Authorize user access to security monitoring center.
     */
    protected function authorizeAccess(): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if (!$user->hasAnyRole(['super_admin', 'admin']) && !$user->can('audit_logs.view')) {
            abort(403, 'Unauthorized. Administrative authorization required to view platform security controls.');
        }
    }

    /**
     * Display Security Dashboard: sessions, failed logins, and authentication events.
     */
    public function index(Request $request): View
    {
        $this->authorizeAccess();

        $currentSessionId = session()->getId();

        // 1. Active sessions from database sessions table
        $sessions = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
            $sessions = DB::table('sessions')
                ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
                ->select([
                    'sessions.id',
                    'sessions.user_id',
                    'sessions.ip_address',
                    'sessions.user_agent',
                    'sessions.last_activity',
                    'users.name as user_name',
                    'users.email as user_email',
                ])
                ->whereNotNull('sessions.user_id')
                ->orderByDesc('sessions.last_activity')
                ->limit(50)
                ->get()
                ->map(function ($s) use ($currentSessionId) {
                    $s->is_current = ($s->id === $currentSessionId);
                    $s->last_seen = Carbon::createFromTimestamp($s->last_activity)->diffForHumans();
                    return $s;
                });
        }

        // 2. Failed logins & security incidents
        $failedLogins = Activity::where('log_name', 'security')
            ->latest('id')
            ->paginate(15, ['*'], 'failed_page');

        // 3. Recent admin authentication events
        $loginHistory = Activity::where('log_name', 'auth')
            ->with('causer')
            ->latest('id')
            ->paginate(15, ['*'], 'auth_page');

        // 4. Metrics summary
        $metrics = [
            'active_sessions' => \Illuminate\Support\Facades\Schema::hasTable('sessions')
                ? DB::table('sessions')->whereNotNull('user_id')->count()
                : 0,
            'failed_logins_24h' => Activity::where('log_name', 'security')
                ->where('event', 'failed_login')
                ->where('created_at', '>=', now()->subHours(24))
                ->count(),
            'lockouts_24h' => Activity::where('log_name', 'security')
                ->where('event', 'account_lockout')
                ->where('created_at', '>=', now()->subHours(24))
                ->count(),
            'unauthorized_roles_24h' => Activity::where('log_name', 'security')
                ->where('event', 'unauthorized_admin_access')
                ->where('created_at', '>=', now()->subHours(24))
                ->count(),
        ];

        return view('admin.security.index', compact('sessions', 'failedLogins', 'loginHistory', 'metrics', 'currentSessionId'));
    }

    /**
     * Terminate a specific user session.
     */
    public function terminateSession(Request $request, string $sessionId): RedirectResponse
    {
        $this->authorizeAccess();

        if (!\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
            return back()->with('error', 'Database session driver is not active.');
        }

        $sessionRow = DB::table('sessions')->where('id', $sessionId)->first();

        if ($sessionRow) {
            DB::table('sessions')->where('id', $sessionId)->delete();

            if (function_exists('activity')) {
                activity('security')
                    ->causedBy(Auth::user())
                    ->event('session_terminated')
                    ->withProperties([
                        'terminated_session_id' => $sessionId,
                        'target_user_id' => $sessionRow->user_id,
                        'target_ip' => $sessionRow->ip_address,
                    ])
                    ->log("Administrator terminated user session '{$sessionId}' (User ID: {$sessionRow->user_id})");
            }

            return back()->with('success', "Session successfully terminated and revoked.");
        }

        return back()->with('info', "Session was already inactive or expired.");
    }

    /**
     * Terminate all other sessions except the current one.
     */
    public function terminateAllOtherSessions(Request $request): RedirectResponse
    {
        $this->authorizeAccess();

        if (!\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
            return back()->with('error', 'Database session driver is not active.');
        }

        $currentSessionId = session()->getId();
        $userId = Auth::id();

        $deletedCount = DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        if (function_exists('activity')) {
            activity('security')
                ->causedBy(Auth::user())
                ->event('sessions_revoked')
                ->withProperties(['revoked_count' => $deletedCount])
                ->log("Revoked {$deletedCount} other active sessions for user ID {$userId}");
        }

        return back()->with('success', "Revoked {$deletedCount} other active session(s). Current session remains active.");
    }
}
