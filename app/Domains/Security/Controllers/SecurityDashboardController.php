<?php

namespace App\Domains\Security\Controllers;

use App\Http\Controllers\BaseResourceController;
use App\Domains\Security\Services\IpLookupService;
use App\Domains\Security\Services\SuspiciousLoginService;
use App\Domains\Security\Models\IpLookupLog;
use App\Domains\Security\Models\SuspiciousLoginAttempt;
use App\Domains\Security\Models\TrustedDevice;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SecurityDashboardController extends BaseResourceController
{
    protected IpLookupService $ipLookupService;
    protected SuspiciousLoginService $suspiciousLoginService;

    public function __construct(
        IpLookupService $ipLookupService,
        SuspiciousLoginService $suspiciousLoginService
    ) {
        $this->ipLookupService = $ipLookupService;
        $this->suspiciousLoginService = $suspiciousLoginService;
        
        parent::__construct();
    }

    protected function initializeController(): void
    {
        $this->resourceName = 'security-dashboard';
        $this->viewPath = 'security.dashboard';
        $this->routePrefix = 'security.dashboard';
    }

    protected function getModelClass(): string
    {
        return IpLookupLog::class;
    }

    public function index(Request $request): View|JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $days = $request->input('days', 30);
        $startDate = now()->subDays($days);

        $dashboardData = [
            'overview' => $this->getSecurityOverview($companyId, $startDate),
            'suspicious_logins' => $this->getSuspiciousLoginsData($companyId, $startDate),
            'ip_intelligence' => $this->getIpIntelligenceData($companyId, $startDate),
            'threat_analysis' => $this->getThreatAnalysisData($companyId, $startDate),
            'trusted_devices' => $this->getTrustedDevicesData($companyId),
            'recent_activity' => $this->getRecentSecurityActivity($companyId, 50),
            'geographic_data' => $this->getGeographicData($companyId, $startDate),
        ];

        if ($request->wantsJson()) {
            return response()->json($dashboardData);
        }

        return view('security.dashboard.index', [
            'dashboardData' => $dashboardData,
            'days' => $days,
            'title' => 'Security Dashboard',
            'breadcrumbs' => [
                ['name' => 'Dashboard', 'url' => route('dashboard')],
                ['name' => 'Security', 'url' => null],
            ],
        ]);
    }

    public function suspiciousLogins(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $status = $request->input('status', 'all');
        $riskLevel = $request->input('risk_level', 'all');
        $days = $request->input('days', 30);

        $query = SuspiciousLoginAttempt::where('company_id', $companyId)
            ->with(['user'])
            ->where('created_at', '>=', now()->subDays($days));

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($riskLevel !== 'all') {
            $riskThresholds = [
                'low' => [0, 39],
                'medium' => [40, 59],
                'high' => [60, 79],
                'critical' => [80, 100],
            ];
            
            if (isset($riskThresholds[$riskLevel])) {
                [$min, $max] = $riskThresholds[$riskLevel];
                $query->whereBetween('risk_score', [$min, $max]);
            }
        }

        $attempts = $query->orderBy('created_at', 'desc')->paginate(25);

        return view('security.dashboard.suspicious-logins', [
            'attempts' => $attempts,
            'filters' => [
                'status' => $status,
                'risk_level' => $riskLevel,
                'days' => $days,
            ],
            'title' => 'Suspicious Login Attempts',
            'breadcrumbs' => [
                ['name' => 'Dashboard', 'url' => route('dashboard')],
                ['name' => 'Security', 'url' => route('security.dashboard.index')],
                ['name' => 'Suspicious Logins', 'url' => null],
            ],
        ]);
    }

    public function ipIntelligence(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $threatLevel = $request->input('threat_level', 'all');
        $country = $request->input('country');
        $days = $request->input('days', 30);

        $query = IpLookupLog::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays($days));

        if ($threatLevel !== 'all') {
            $query->where('threat_level', $threatLevel);
        }

        if ($country) {
            $query->where('country_code', $country);
        }

        $ipLogs = $query->orderBy('last_lookup_at', 'desc')->paginate(25);

        $statistics = [
            'total_ips' => IpLookupLog::where('company_id', $companyId)->count(),
            'suspicious_ips' => IpLookupLog::where('company_id', $companyId)->bySuspicious(true)->count(),
            'unique_countries' => IpLookupLog::where('company_id', $companyId)->distinct('country_code')->count('country_code'),
            'threat_levels' => IpLookupLog::where('company_id', $companyId)
                ->select('threat_level', DB::raw('count(*) as count'))
                ->groupBy('threat_level')
                ->pluck('count', 'threat_level')
                ->toArray(),
        ];

        return view('security.dashboard.ip-intelligence', [
            'ipLogs' => $ipLogs,
            'statistics' => $statistics,
            'filters' => [
                'threat_level' => $threatLevel,
                'country' => $country,
                'days' => $days,
            ],
            'title' => 'IP Intelligence',
            'breadcrumbs' => [
                ['name' => 'Dashboard', 'url' => route('dashboard')],
                ['name' => 'Security', 'url' => route('security.dashboard.index')],
                ['name' => 'IP Intelligence', 'url' => null],
            ],
        ]);
    }

    public function trustedDevices(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $userId = $request->input('user_id');
        $status = $request->input('status', 'active');

        $query = TrustedDevice::where('company_id', $companyId)
            ->with(['user']);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'expired') {
            $query->expired();
        } elseif ($status === 'revoked') {
            $query->where('is_active', false);
        }

        $devices = $query->orderBy('last_used_at', 'desc')->paginate(25);

        $statistics = [
            'total' => TrustedDevice::where('company_id', $companyId)->count(),
            'active' => TrustedDevice::where('company_id', $companyId)->active()->count(),
            'expired' => TrustedDevice::where('company_id', $companyId)->expired()->count(),
            'revoked' => TrustedDevice::where('company_id', $companyId)->where('is_active', false)->count(),
        ];

        return view('security.dashboard.trusted-devices', [
            'devices' => $devices,
            'statistics' => $statistics,
            'filters' => [
                'user_id' => $userId,
                'status' => $status,
            ],
            'title' => 'Trusted Devices',
            'breadcrumbs' => [
                ['name' => 'Dashboard', 'url' => route('dashboard')],
                ['name' => 'Security', 'url' => route('security.dashboard.index')],
                ['name' => 'Trusted Devices', 'url' => null],
            ],
        ]);
    }

    public function revokeDevice(Request $request, TrustedDevice $device)
    {
        if ($device->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $device->revoke();

        AuditLog::logSecurity('Trusted Device Revoked', [
            'device_id' => $device->id,
            'user_id' => $device->user_id,
            'device_name' => $device->getDeviceString(),
            'revoked_by' => auth()->user()->id,
        ], AuditLog::SEVERITY_WARNING);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Device access revoked successfully.');
    }

    public function blockIp(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'reason' => 'required|string|max:255',
        ]);

        $ipAddress = $request->input('ip_address');
        $reason = $request->input('reason');

        // Update or create IP lookup record with critical threat level
        $ipLookup = IpLookupLog::where('company_id', auth()->user()->company_id)
            ->where('ip_address', $ipAddress)
            ->first();

        if ($ipLookup) {
            $ipLookup->update(['threat_level' => IpLookupLog::THREAT_LEVEL_CRITICAL]);
        } else {
            // Create a basic record for the blocked IP
            IpLookupLog::create([
                'company_id' => auth()->user()->company_id,
                'ip_address' => $ipAddress,
                'threat_level' => IpLookupLog::THREAT_LEVEL_CRITICAL,
                'lookup_source' => 'manual_block',
                'cached_until' => now()->addYears(1),
                'lookup_count' => 1,
                'last_lookup_at' => now(),
            ]);
        }

        AuditLog::logSecurity('IP Address Blocked', [
            'ip_address' => $ipAddress,
            'reason' => $reason,
            'blocked_by' => auth()->user()->id,
        ], AuditLog::SEVERITY_CRITICAL);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', "IP address {$ipAddress} has been blocked.");
    }

    public function unblockIp(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
        ]);

        $ipAddress = $request->input('ip_address');

        $ipLookup = IpLookupLog::where('company_id', auth()->user()->company_id)
            ->where('ip_address', $ipAddress)
            ->first();

        if ($ipLookup && $ipLookup->threat_level === IpLookupLog::THREAT_LEVEL_CRITICAL) {
            $ipLookup->update(['threat_level' => IpLookupLog::THREAT_LEVEL_LOW]);

            AuditLog::logSecurity('IP Address Unblocked', [
                'ip_address' => $ipAddress,
                'unblocked_by' => auth()->user()->id,
            ], AuditLog::SEVERITY_INFO);

            if ($request->wantsJson()) {
                return response()->json(['success' => true]);
            }

            return redirect()->back()->with('success', "IP address {$ipAddress} has been unblocked.");
        }

        if ($request->wantsJson()) {
            return response()->json(['error' => 'IP address not found or not blocked'], 404);
        }

        return redirect()->back()->with('error', 'IP address not found or not blocked.');
    }

    protected function getSecurityOverview($companyId, $startDate)
    {
        return [
            'total_login_attempts' => AuditLog::where('company_id', $companyId)
                ->where('event_type', AuditLog::EVENT_LOGIN)
                ->where('created_at', '>=', $startDate)
                ->count(),
            'suspicious_attempts' => SuspiciousLoginAttempt::where('company_id', $companyId)
                ->where('created_at', '>=', $startDate)
                ->count(),
            'blocked_attempts' => SuspiciousLoginAttempt::where('company_id', $companyId)
                ->where('status', SuspiciousLoginAttempt::STATUS_DENIED)
                ->where('created_at', '>=', $startDate)
                ->count(),
            'trusted_devices' => TrustedDevice::where('company_id', $companyId)
                ->active()
                ->count(),
            'unique_ips' => IpLookupLog::where('company_id', $companyId)
                ->where('created_at', '>=', $startDate)
                ->distinct('ip_address')
                ->count('ip_address'),
            'high_risk_ips' => IpLookupLog::where('company_id', $companyId)
                ->whereIn('threat_level', [IpLookupLog::THREAT_LEVEL_HIGH, IpLookupLog::THREAT_LEVEL_CRITICAL])
                ->where('created_at', '>=', $startDate)
                ->count(),
        ];
    }

    protected function getSuspiciousLoginsData($companyId, $startDate)
    {
        $recentAttempts = SuspiciousLoginAttempt::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $statusBreakdown = SuspiciousLoginAttempt::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $riskBreakdown = SuspiciousLoginAttempt::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('CASE 
                    WHEN risk_score >= 80 THEN "critical"
                    WHEN risk_score >= 60 THEN "high" 
                    WHEN risk_score >= 40 THEN "medium"
                    ELSE "low" 
                END as risk_level'),
                DB::raw('count(*) as count')
            )
            ->groupBy('risk_level')
            ->pluck('count', 'risk_level')
            ->toArray();

        return [
            'recent_attempts' => $recentAttempts,
            'status_breakdown' => $statusBreakdown,
            'risk_breakdown' => $riskBreakdown,
        ];
    }

    protected function getIpIntelligenceData($companyId, $startDate)
    {
        $topCountries = IpLookupLog::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('country_code')
            ->select('country_code', 'country', DB::raw('count(*) as count'))
            ->groupBy(['country_code', 'country'])
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        $threatLevels = IpLookupLog::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->select('threat_level', DB::raw('count(*) as count'))
            ->groupBy('threat_level')
            ->pluck('count', 'threat_level')
            ->toArray();

        $suspiciousIps = IpLookupLog::where('company_id', $companyId)
            ->bySuspicious(true)
            ->where('created_at', '>=', $startDate)
            ->orderBy('lookup_count', 'desc')
            ->limit(10)
            ->get();

        return [
            'top_countries' => $topCountries,
            'threat_levels' => $threatLevels,
            'suspicious_ips' => $suspiciousIps,
        ];
    }

    protected function getThreatAnalysisData($companyId, $startDate)
    {
        $threatTrends = IpLookupLog::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('created_at::date as date'),
                DB::raw('SUM(CASE WHEN threat_level = "critical" THEN 1 ELSE 0 END) as critical'),
                DB::raw('SUM(CASE WHEN threat_level = "high" THEN 1 ELSE 0 END) as high'),
                DB::raw('SUM(CASE WHEN threat_level = "medium" THEN 1 ELSE 0 END) as medium'),
                DB::raw('SUM(CASE WHEN threat_level = "low" THEN 1 ELSE 0 END) as low')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $vpnProxyDetection = IpLookupLog::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('SUM(CASE WHEN is_vpn = 1 THEN 1 ELSE 0 END) as vpn_count'),
                DB::raw('SUM(CASE WHEN is_proxy = 1 THEN 1 ELSE 0 END) as proxy_count'),
                DB::raw('SUM(CASE WHEN is_tor = 1 THEN 1 ELSE 0 END) as tor_count')
            )
            ->first();

        return [
            'threat_trends' => $threatTrends,
            'vpn_proxy_detection' => $vpnProxyDetection,
        ];
    }

    protected function getTrustedDevicesData($companyId)
    {
        $deviceStats = [
            'total' => TrustedDevice::where('company_id', $companyId)->count(),
            'active' => TrustedDevice::where('company_id', $companyId)->active()->count(),
            'expired' => TrustedDevice::where('company_id', $companyId)->expired()->count(),
            'revoked' => TrustedDevice::where('company_id', $companyId)->where('is_active', false)->count(),
        ];

        $recentDevices = TrustedDevice::where('company_id', $companyId)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'stats' => $deviceStats,
            'recent_devices' => $recentDevices,
        ];
    }

    protected function getRecentSecurityActivity($companyId, $limit = 50)
    {
        return AuditLog::where('company_id', $companyId)
            ->where('event_type', AuditLog::EVENT_SECURITY)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    protected function getGeographicData($companyId, $startDate)
    {
        $loginsByCountry = AuditLog::where('company_id', $companyId)
            ->where('event_type', AuditLog::EVENT_LOGIN)
            ->where('response_status', '<', 400)
            ->where('created_at', '>=', $startDate)
            ->whereJsonLength('metadata', '>', 0)
            ->get()
            ->groupBy(function ($item) {
                return $item->metadata['ip_country_code'] ?? 'Unknown';
            })
            ->map(function ($items) {
                return $items->count();
            })
            ->sortDesc()
            ->take(10);

        return [
            'logins_by_country' => $loginsByCountry,
        ];
    }
}