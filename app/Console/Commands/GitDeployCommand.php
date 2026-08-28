<?php

namespace App\Console\Commands;

use App\Services\GitDeployer;
use Illuminate\Console\Command;

/**
 * The command-line half of the deploy button.
 *
 * Both share {@see GitDeployer}, so a deploy run from a terminal does exactly
 * what the dashboard does — there is no second implementation to drift.
 */
class GitDeployCommand extends Command
{
    protected $signature = 'git:deploy {--check : Report what is waiting without pulling anything}';

    protected $description = 'Pull the latest code, run migrations and rebuild the caches';

    public function handle(GitDeployer $deployer): int
    {
        $support = $deployer->support();

        if (! $support['ok']) {
            $this->error($support['reason']);

            return self::FAILURE;
        }

        $status = $deployer->status();

        if (! $status['ok']) {
            $this->error('Could not read the repository: '.$status['error']);

            if ($hint = $deployer->diagnose($status['error'])) {
                $this->warn($hint);
            }

            return self::FAILURE;
        }

        $this->line("On branch <info>{$status['branch']}</info>, currently at {$status['current']}");

        if ($status['upToDate']) {
            $this->info('Already up to date. Nothing to deploy.');

            return self::SUCCESS;
        }

        $this->line("{$status['behind']} commit(s) waiting:");

        foreach ($status['commits'] as $commit) {
            $this->line('  '.$commit);
        }

        if ($this->option('check')) {
            return self::SUCCESS;
        }

        $this->info('Pulling…');
        $pull = $deployer->pull($status['branch']);

        if (! $pull['ok']) {
            $this->error('Git pull failed: '.$pull['error']);

            if ($hint = $deployer->diagnose($pull['error'])) {
                $this->warn($hint);
            }

            return self::FAILURE;
        }

        $this->line($pull['output']);

        $this->info('Migrating…');
        $migrate = $deployer->migrate();

        if (! $migrate['ok']) {
            // The code is already on disk at this point, so the caches are
            // still rebuilt below; leaving stale ones would compound it.
            $this->error('Migrations failed: '.$migrate['error']);
        } else {
            $this->line($migrate['output']);
        }

        $this->info('Publishing assets…');
        $assets = $deployer->publishAssets();

        if (! $assets['ok']) {
            $this->error('Could not publish assets: '.$assets['error']);
        } else {
            $this->line($assets['output']);
        }

        $this->info('Rebuilding caches…');
        $caches = $deployer->refreshCaches();

        if (! $caches['ok']) {
            $this->error('Cache rebuild failed: '.$caches['error']);

            return self::FAILURE;
        }

        $this->line($caches['output']);

        if (! $migrate['ok']) {
            $this->error('Deployed, but migrations did not finish. Check the database before relying on the site.');

            return self::FAILURE;
        }

        $this->info('Deployment completed successfully.');

        return self::SUCCESS;
    }
}
