<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl;

use Illuminate\Support\Facades\Event;
use LeviLabs\LaravelWayl\Console\Commands\SyncWaylStatuses;
use LeviLabs\LaravelWayl\Contracts\WaylLinkServiceContract;
use LeviLabs\LaravelWayl\Contracts\WaylProductServiceContract;
use LeviLabs\LaravelWayl\Contracts\WaylRefundServiceContract;
use LeviLabs\LaravelWayl\Events\Links\LinkCreated;
use LeviLabs\LaravelWayl\Events\Links\LinkStatusChecked;
use LeviLabs\LaravelWayl\Events\Refunds\RefundCancelled;
use LeviLabs\LaravelWayl\Events\Refunds\RefundCreated;
use LeviLabs\LaravelWayl\Events\Refunds\RefundStatusChecked;
use LeviLabs\LaravelWayl\Listeners\PersistWaylLink;
use LeviLabs\LaravelWayl\Listeners\PersistWaylRefund;
use LeviLabs\LaravelWayl\Services\WaylLinkService;
use LeviLabs\LaravelWayl\Services\WaylProductService;
use LeviLabs\LaravelWayl\Services\WaylRefundService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WaylServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('wayl')
            ->hasConfigFile('wayl')
            ->hasMigrations('create_wayl_links_table', 'create_wayl_refunds_table')
            ->runsMigrations()
            ->hasCommand(SyncWaylStatuses::class);
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(WaylLinkServiceContract::class, WaylLinkService::class);
        $this->app->singleton(WaylRefundServiceContract::class, WaylRefundService::class);
        $this->app->singleton(WaylProductServiceContract::class, WaylProductService::class);
    }

    public function packageBooted(): void
    {
        Event::listen(LinkCreated::class, [PersistWaylLink::class, 'onCreated']);
        Event::listen(LinkStatusChecked::class, [PersistWaylLink::class, 'onStatusChecked']);
        Event::listen(RefundCreated::class, [PersistWaylRefund::class, 'onCreated']);
        Event::listen(RefundStatusChecked::class, [PersistWaylRefund::class, 'onStatusChecked']);
        Event::listen(RefundCancelled::class, [PersistWaylRefund::class, 'onCancelled']);
    }
}
