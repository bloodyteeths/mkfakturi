<?php

namespace App\Http\Middleware;

use App\Models\FileDisk;
use App\Space\InstallUtils;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip health check endpoints to avoid database dependency
        $healthCheckPaths = ['/health', '/up', '/ping', '/ready'];
        if (in_array($request->path(), $healthCheckPaths)) {
            return $next($request);
        }

        if (InstallUtils::isDbCreated() && InstallUtils::tableExists('file_disks')) {
            if ($request->has('file_disk_id')) {
                $file_disk = FileDisk::find($request->file_disk_id);
            } else {
                $file_disk = FileDisk::whereSetAsDefault(true)->first();
            }

            if ($file_disk) {
                $file_disk->setConfig();
            }
        }

        return $next($request);
    }
}
