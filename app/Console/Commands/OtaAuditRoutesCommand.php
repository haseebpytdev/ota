<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route as RouteFacade;

class OtaAuditRoutesCommand extends Command
{
    protected $signature = 'ota:audit-routes';

    protected $description = 'Audit route exposure, middleware, and risky mutating endpoints';

    public function handle(): int
    {
        $routes = collect(RouteFacade::getRoutes()->getRoutes());

        $this->info('=== OTA Route Audit ===');
        $this->line('Total routes: '.$routes->count());
        $this->newLine();

        $this->outputGroup('Public routes', $routes->filter(fn (Route $route): bool => ! in_array('auth', $route->gatherMiddleware(), true)));
        $this->outputGroup('Authenticated routes', $routes->filter(fn (Route $route): bool => in_array('auth', $route->gatherMiddleware(), true)));
        $this->outputGroup('Admin routes', $this->byPrefix($routes, 'admin.'));
        $this->outputGroup('Staff routes', $this->byPrefix($routes, 'staff.'));
        $this->outputGroup('Agent routes', $this->byPrefix($routes, 'agent.'));
        $this->outputGroup('Customer routes', $this->byPrefix($routes, 'customer.'));

        $risky = $routes->filter(function (Route $route): bool {
            $methods = array_diff($route->methods(), ['HEAD']);

            return collect($methods)->contains(fn (string $method): bool => in_array($method, ['POST', 'PATCH', 'PUT', 'DELETE'], true));
        });
        $this->outputGroup('Risky mutating routes', $risky);

        $missingAuthWarning = $risky->filter(fn (Route $route): bool => ! in_array('auth', $route->gatherMiddleware(), true))
            ->filter(function (Route $route): bool {
                $name = (string) $route->getName();

                return ! str_starts_with($name, 'guest.')
                    && ! str_starts_with($name, 'lookup-booking.')
                    && ! str_starts_with($name, 'booking.')
                    && ! str_contains($name, 'login')
                    && ! str_contains($name, 'password');
            });

        $this->warn('Routes that may be missing auth (heuristic warning only)');
        if ($missingAuthWarning->isEmpty()) {
            $this->line('  none');
        } else {
            foreach ($missingAuthWarning as $route) {
                $this->line('  '.$this->routeSummary($route));
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Route>  $routes
     */
    protected function outputGroup(string $title, Collection $routes): void
    {
        $this->info("{$title}: ".$routes->count());
        foreach ($routes->take(25) as $route) {
            $this->line('  '.$this->routeSummary($route));
        }
        if ($routes->count() > 25) {
            $this->line('  ... +'.($routes->count() - 25).' more');
        }
        $this->newLine();
    }

    /**
     * @param  Collection<int, Route>  $routes
     * @return Collection<int, Route>
     */
    protected function byPrefix(Collection $routes, string $prefix): Collection
    {
        return $routes->filter(fn (Route $route): bool => str_starts_with((string) $route->getName(), $prefix));
    }

    protected function routeSummary(Route $route): string
    {
        $methods = implode('|', array_diff($route->methods(), ['HEAD']));
        $middleware = implode(',', $route->gatherMiddleware());

        return sprintf(
            '[%s] %s name=%s mw=%s',
            $methods,
            $route->uri(),
            $route->getName() ?: '-',
            $middleware
        );
    }
}
