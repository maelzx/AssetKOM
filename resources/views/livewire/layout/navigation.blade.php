<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-base-300/70 bg-base-100/90 shadow-base-content/5 backdrop-blur-xl">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            <div class="flex items-center gap-6">
                <x-brand />

                <div class="hidden items-center gap-1 lg:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</x-nav-link>
                    <x-nav-link :href="route('assets.index')" :active="request()->routeIs('assets.*')" wire:navigate>{{ __('Assets') }}</x-nav-link>

                    <x-nav-group :label="__('Operations')" :active="request()->routeIs('assignments.*', 'maintenance.*', 'labels.*', 'imports.*', 'scan.index')">
                        <x-dropdown-link :href="route('assignments.index')" :active="request()->routeIs('assignments.*')" wire:navigate>{{ __('Assignments') }}</x-dropdown-link>
                        <x-dropdown-link :href="route('maintenance.index')" :active="request()->routeIs('maintenance.*')" wire:navigate>{{ __('Maintenance') }}</x-dropdown-link>
                        <x-dropdown-link :href="route('labels.index')" :active="request()->routeIs('labels.*')" wire:navigate>{{ __('Labels') }}</x-dropdown-link>
                        <x-dropdown-link :href="route('scan.index')" :active="request()->routeIs('scan.index')" wire:navigate>{{ __('Scan / find') }}</x-dropdown-link>
                        @can('manage-assets')
                            <x-dropdown-link :href="route('imports.assets')" :active="request()->routeIs('imports.*')" wire:navigate>{{ __('Import') }}</x-dropdown-link>
                        @endcan
                    </x-nav-group>

                    @can('view-reports')
                        <x-nav-group :label="__('Insights')" :active="request()->routeIs('reports.*', 'activity.*')">
                            <x-dropdown-link :href="route('reports.index')" :active="request()->routeIs('reports.*')" wire:navigate>{{ __('Reports') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('activity.index')" :active="request()->routeIs('activity.*')" wire:navigate>{{ __('Activity') }}</x-dropdown-link>
                        </x-nav-group>
                    @endcan

                    @can('manage-catalog')
                        <x-nav-group :label="__('Admin')" :active="request()->routeIs('categories.*', 'locations.*', 'settings', 'users.*')">
                            @can('manage-users')
                                <x-dropdown-link :href="route('users.index')" :active="request()->routeIs('users.*')" wire:navigate>{{ __('Users') }}</x-dropdown-link>
                            @endcan
                            <x-dropdown-link :href="route('categories.index')" :active="request()->routeIs('categories.*')" wire:navigate>{{ __('Categories') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('locations.index')" :active="request()->routeIs('locations.*')" wire:navigate>{{ __('Locations') }}</x-dropdown-link>
                            @can('manage-settings')
                                <x-dropdown-link :href="route('settings')" :active="request()->routeIs('settings')" wire:navigate>{{ __('Settings') }}</x-dropdown-link>
                            @endcan
                        </x-nav-group>
                    @endcan
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="btn btn-ghost btn-circle"
                    aria-label="{{ __('Toggle theme') }}"
                    onclick="(function(){var r=document.documentElement;var c=r.getAttribute('data-theme');var d=c?c==='assetkom-dark':window.matchMedia('(prefers-color-scheme: dark)').matches;var n=d?'assetkom':'assetkom-dark';r.setAttribute('data-theme',n);try{localStorage.setItem('theme',n)}catch(e){}})()"
                >
                    <svg class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                    <svg class="hidden h-5 w-5 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.36 6.36l-.71-.71M6.34 6.34l-.71-.71m12.73 0l-.71.71M6.34 17.66l-.71.71M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg>
                </button>

                <div class="hidden sm:block">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="btn btn-ghost h-10 gap-2 rounded-xl px-2.5">
                                <span class="grid size-7 place-items-center rounded-full bg-primary/10 text-xs font-bold text-primary" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                                <span class="max-w-36 truncate" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></span>
                                <svg class="h-4 w-4 opacity-60" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile')" :active="request()->routeIs('profile')" wire:navigate>{{ __('Profile') }}</x-dropdown-link>

                            <button wire:click="logout" class="w-full text-start">
                                <x-dropdown-link>{{ __('Log Out') }}</x-dropdown-link>
                            </button>
                        </x-slot>
                    </x-dropdown>
                </div>

                <button class="btn btn-ghost btn-square rounded-xl lg:hidden" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="mobile-navigation" aria-label="{{ __('Menu') }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div id="mobile-navigation" x-show="open" x-transition class="border-t border-base-300 bg-base-100 lg:hidden">
        <div class="mx-auto max-w-7xl space-y-1 px-4 py-3 sm:px-6">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('assets.index')" :active="request()->routeIs('assets.*')" wire:navigate>{{ __('Assets') }}</x-responsive-nav-link>

            <p class="px-3 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-base-content/40">{{ __('Operations') }}</p>
            <x-responsive-nav-link :href="route('assignments.index')" :active="request()->routeIs('assignments.*')" wire:navigate>{{ __('Assignments') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('maintenance.index')" :active="request()->routeIs('maintenance.*')" wire:navigate>{{ __('Maintenance') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('labels.index')" :active="request()->routeIs('labels.*')" wire:navigate>{{ __('Labels') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('scan.index')" :active="request()->routeIs('scan.index')" wire:navigate>{{ __('Scan / find') }}</x-responsive-nav-link>
            @can('manage-assets')
                <x-responsive-nav-link :href="route('imports.assets')" :active="request()->routeIs('imports.*')" wire:navigate>{{ __('Import') }}</x-responsive-nav-link>
            @endcan

            @can('view-reports')
                <p class="px-3 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-base-content/40">{{ __('Insights') }}</p>
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')" wire:navigate>{{ __('Reports') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('activity.index')" :active="request()->routeIs('activity.*')" wire:navigate>{{ __('Activity') }}</x-responsive-nav-link>
            @endcan

            @can('manage-catalog')
                <p class="px-3 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-base-content/40">{{ __('Admin') }}</p>
                @can('manage-users')
                    <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" wire:navigate>{{ __('Users') }}</x-responsive-nav-link>
                @endcan
                <x-responsive-nav-link :href="route('categories.index')" :active="request()->routeIs('categories.*')" wire:navigate>{{ __('Categories') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('locations.index')" :active="request()->routeIs('locations.*')" wire:navigate>{{ __('Locations') }}</x-responsive-nav-link>
                @can('manage-settings')
                    <x-responsive-nav-link :href="route('settings')" :active="request()->routeIs('settings')" wire:navigate>{{ __('Settings') }}</x-responsive-nav-link>
                @endcan
            @endcan

            <div class="mt-2 border-t border-base-300 pt-2 sm:hidden">
                <div class="px-3 py-1">
                    <div class="text-base font-medium" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                    <div class="text-sm text-base-content/60">{{ auth()->user()->email }}</div>
                </div>

                <x-responsive-nav-link :href="route('profile')" :active="request()->routeIs('profile')" wire:navigate>{{ __('Profile') }}</x-responsive-nav-link>

                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>{{ __('Log Out') }}</x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
