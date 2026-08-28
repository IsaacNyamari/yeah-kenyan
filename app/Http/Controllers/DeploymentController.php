<?php

namespace App\Http\Controllers;

use App\Services\AppVersion;
use App\Services\GitDeployer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Plain JSON endpoints for the deploy screen.
 *
 * Deliberately not Livewire. A deploy replaces the application's own code
 * halfway through, and a Livewire page open across that change sends the next
 * request with a snapshot produced by the previous version of the component —
 * which fails to hydrate the moment that component's properties differ.
 * Ordinary requests carry no such state, so the browser can keep driving the
 * steps while the code beneath it changes.
 */
class DeploymentController extends Controller
{
    /**
     * What is waiting, and whether this server can deploy at all.
     */
    public function check(GitDeployer $deployer, AppVersion $version): JsonResponse
    {
        $support = $deployer->support();

        if (! $support['ok']) {
            return response()->json([
                'ok' => false,
                'blocked' => $support['reason'],
                'installedVersion' => $version->current(),
            ]);
        }

        $status = $deployer->status();
        $latest = $status['latestVersion'];

        return response()->json([
            'ok' => $status['ok'],
            'blocked' => null,
            'remote' => $deployer->remote(),
            'branch' => $status['branch'],
            'behind' => $status['behind'],
            'upToDate' => $status['upToDate'],
            'commits' => $status['commits'],
            'current' => $status['current'],
            'installedVersion' => $status['installedVersion'],
            'latestVersion' => $latest,
            'releaseAvailable' => is_string($latest) && $version->isBehind($latest),
            'error' => $status['error'],
            'hint' => $status['ok'] ? null : $deployer->diagnose($status['error']),
        ]);
    }

    /**
     * Run one step of the deploy.
     */
    public function step(Request $request, string $step, GitDeployer $deployer): JsonResponse
    {
        $branch = $request->string('branch')->trim()->value();

        // The branch reaches here from the browser, so it is checked rather
        // than handed to git as-is.
        if (preg_match('/^[A-Za-z0-9._\/-]{1,100}$/', $branch) !== 1) {
            $branch = 'main';
        }

        $result = match ($step) {
            'pull' => $deployer->pull($branch),
            'migrate' => $deployer->migrate(),
            'assets' => $deployer->publishAssets(),
            default => $deployer->refreshCaches(),
        };

        return response()->json([
            'ok' => $result['ok'],
            'output' => $result['output'],
            'error' => $result['error'],
            'hint' => $result['ok'] ? null : $deployer->diagnose($result['error']),
        ]);
    }
}
