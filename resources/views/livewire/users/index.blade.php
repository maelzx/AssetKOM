<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $role = 'staff';

    public string $password = '';

    public function mount(): void
    {
        Gate::authorize('manage-users');
    }

    public function create(): void
    {
        Gate::authorize('manage-users');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(Role::values())],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $user = new User;
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = Role::from($validated['role']);
        $user->password = $validated['password'];
        $user->email_verified_at = now();
        $user->save();

        $this->reset(['name', 'email', 'password']);
        $this->role = Role::Staff->value;
        $this->dispatch('user-saved');
    }

    public function updateRole(int $id, string $role): void
    {
        Gate::authorize('manage-users');

        $target = Role::from($role);
        $user = User::findOrFail($id);

        if ($user->isAdmin() && $target !== Role::Admin && $this->adminCount() <= 1) {
            $this->addError('users', __('At least one administrator must remain.'));

            return;
        }

        $user->role = $target;
        $user->save();
    }

    public function delete(int $id): void
    {
        Gate::authorize('manage-users');

        $user = User::findOrFail($id);

        if ($user->is(auth()->user())) {
            $this->addError('users', __('You cannot delete your own account here.'));

            return;
        }

        if ($user->isAdmin() && $this->adminCount() <= 1) {
            $this->addError('users', __('At least one administrator must remain.'));

            return;
        }

        $user->delete();
    }

    protected function adminCount(): int
    {
        return User::query()->where('role', Role::Admin->value)->count();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'users' => User::query()->orderBy('name')->get(),
            'roles' => Role::cases(),
        ];
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
        <x-page-header :title="__('Users')" :subtitle="__('Provision accounts and manage roles.')" />

        <div class="card bg-base-100 p-6">
            <h3 class="text-lg font-medium text-base-content">{{ __('New user') }}</h3>

            <form wire:submit="create" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <x-input-label for="user_name" :value="__('Name')" />
                    <x-text-input wire:model="name" id="user_name" type="text" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="user_email" :value="__('Email')" />
                    <x-text-input wire:model="email" id="user_email" type="email" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="user_role" :value="__('Role')" />
                    <select wire:model="role" id="user_role" class="select select-bordered mt-1 block w-full">
                        @foreach ($roles as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="user_password" :value="__('Temporary password')" />
                    <x-text-input wire:model="password" id="user_password" type="text" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>

                <div class="sm:col-span-2 lg:col-span-4 flex items-center gap-3">
                    <x-primary-button>{{ __('Create user') }}</x-primary-button>
                    <x-action-message on="user-saved">{{ __('User created.') }}</x-action-message>
                </div>
            </form>
        </div>

        @error('users')
            <div class="rounded-lg border border-error/40 bg-error/10 p-3 text-sm text-error">{{ $message }}</div>
        @enderror

        <x-data-table>
            <x-slot name="table">
                <table class="table table-sm">
                    <thead class="bg-base-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Email') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Role') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Verified') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-300">
                        @foreach ($users as $user)
                            <tr wire:key="user-{{ $user->id }}" class="hover:bg-base-200">
                                <td class="px-4 py-3 text-sm text-base-content">
                                    {{ $user->name }}
                                    @if ($user->is(auth()->user()))
                                        <span class="ms-1 text-xs text-base-content/50">({{ __('you') }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-base-content/70">{{ $user->email }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <select
                                        wire:change="updateRole({{ $user->id }}, $event.target.value)"
                                        class="select select-bordered select-sm"
                                    >
                                        @foreach ($roles as $option)
                                            <option value="{{ $option->value }}" @selected($user->role === $option)>{{ $option->label() }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($user->email_verified_at)
                                        <span class="text-success">{{ __('Yes') }}</span>
                                    @else
                                        <span class="text-warning">{{ __('No') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                    @unless ($user->is(auth()->user()))
                                        <button wire:click="delete({{ $user->id }})" wire:confirm="{{ __('Delete this user?') }}" class="text-error hover:opacity-80">{{ __('Delete') }}</button>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-slot>

            <x-slot name="cards">
                <ul class="divide-y divide-base-300">
                    @foreach ($users as $user)
                        <li class="space-y-2 p-4" wire:key="user-card-{{ $user->id }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-base-content">{{ $user->name }}</p>
                                    <p class="truncate text-xs text-base-content/60">{{ $user->email }}</p>
                                </div>
                                <span class="shrink-0 text-xs {{ $user->email_verified_at ? 'text-success' : 'text-warning' }}">
                                    {{ $user->email_verified_at ? __('Verified') : __('Unverified') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <select wire:change="updateRole({{ $user->id }}, $event.target.value)" class="select select-bordered select-sm">
                                    @foreach ($roles as $option)
                                        <option value="{{ $option->value }}" @selected($user->role === $option)>{{ $option->label() }}</option>
                                    @endforeach
                                </select>
                                @unless ($user->is(auth()->user()))
                                    <button wire:click="delete({{ $user->id }})" wire:confirm="{{ __('Delete this user?') }}" class="text-sm text-error hover:opacity-80">{{ __('Delete') }}</button>
                                @endunless
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-slot>
        </x-data-table>
    </div>
</div>
