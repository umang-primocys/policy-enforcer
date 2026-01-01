<?php

namespace Primo\PolicyEnforcer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class CentralPolicyEnforcer
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedUris = config('policy-enforcer.allowed_uris', []);

        // Skip token verification for whitelisted routes
        if ($request->is($allowedUris)) {
            return $next($request);
        }

        if ($this->verifyToken()) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized access. Invalid or missing token.'
        ], 400);
    }

    private function getMacAddress()
    {
        $mac = @exec('getmac');
        return !empty($mac) ? trim(explode(" ", $mac)[0]) : null;
    }

    private function getServerIP()
    {
        try {
            return file_get_contents('https://api.ipify.org');
        } catch (\Exception $e) {
            Log::error('IP Fetch Error: ' . $e->getMessage());
            return 'UNKNOWN';
        }
    }

    private function verifyToken()
    {
        try {
            $tokenFilePath = base_path('token/validatedToken.txt');

            // File must exist
            if (!File::exists($tokenFilePath)) {
                Log::error("Token file not found: " . $tokenFilePath);
                return false;
            }

            // Check file age
            $lastModified = File::lastModified($tokenFilePath);
            $ageInHours = (time() - $lastModified) / 3600;
            Log::info("Token file age: {$ageInHours} hours");

            // If file is < 4 hours old → valid
            if ($ageInHours < 4) {
                return true;
            }

            // Validate via API
            $token = trim(File::get($tokenFilePath));
            $apiUrl = config('policy-enforcer.verify_url');
            $serverIp = $this->getServerIP();
            $mac = $this->getMacAddress();

            $response = Http::post($apiUrl, [
                "server_ip"   => $serverIp,
                "mac_address" => $mac ?: $serverIp,
                "token"       => $token,
            ]);

            $data = $response->json();
            Log::info('Verification API Response:', $data);

            if (isset($data['success']) && $data['success'] === true) {
                // Delete file if valid
                File::delete($tokenFilePath);
                Log::info("Valid token verified. File deleted.");
                return true;
            }

            Log::error("Token verification failed.");
            return false;

        } catch (\Exception $e) {
            Log::error('Verification Error: ' . $e->getMessage());
            return false;
        }
    }
}
