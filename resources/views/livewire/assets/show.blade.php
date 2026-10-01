<?php

use App\Enums\AssetCondition;
use App\Exceptions\AssetAssignmentException;
use App\Models\Asset;
use App\Models\Location;
use App\Models\User;
use App\Services\AssetAssignmentService;
use App\Support\AssetQrCode;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Asset $asset;

    public bool $showCheckout = false;

    public string $assignableType = 'user';

    public ?int $assignableId = null;

    public ?string $expectedReturnAt = null;

    public string $conditionOut = 'good';

    public string $checkoutNotes = '';

    public bool $showCheckin = false;

    public string $conditionIn = 'good';

    public string $checkinNotes = '';

    public function mount(Asset $asset): void
    {
        $this->asset = $asset;
        $this->refreshAsset();
    }

    protected function refreshAsset(): void
    {
        $this->asset = $this->asset->fresh()->load([
            'category',
            'location',
            'creator',
            'activeAssignment.assignable',
            'assignments.assignable',
            'assignments.assigner',
        ]);
    }

    #[On('asset-updated')]
    public function onAssetUpdated(): void
    {
        $this->refreshAsset();
    }

    public function openCheckout(): void
    {
        $this->reset(['assignableId', 'expectedReturnAt', 'checkoutNotes']);
        $this->assignableType = 'user';
        $this->conditionOut = AssetCondition::Good->value;
        $this->showCheckin = false;
        $this->showCheckout = true;
    }

    public function cancelCheckout(): void
    {
        $this->showCheckout = false;
        $this->resetValidation();
    }

    public function openCheckin(): void
    {
        $this->conditionIn = $this->asset->activeAssignment?->condition_out?->value ?? AssetCondition::Good->value;
        $this->checkinNotes = '';
        $this->showCheckout = false;
        $this->showCheckin = true;
    }

    public function cancelCheckin(): void
    {
        $this->showCheckin = false;
        $this->resetValidation();
    }

    public function checkout(AssetAssignmentService $service): void
    {
        $this->validate([
            'assignableType' => ['required', Rule::in(['user', 'location'])],
            'assignableId' => ['required', 'integer'],
            'expectedReturnAt' => ['nullable', 'date', 'after_or_equal:today'],
            'conditionOut' => ['required', Rule::in(AssetCondition::values())],
            'checkoutNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $assignable = $this->assignableType === 'user'
            ? User::findOrFail($this->assignableId)
            : Location::findOrFail($this->assignableId);

        try {
            $service->checkout(
                $this->asset,
                $assignable,
                auth()->user(),
                $this->expectedReturnAt,
                AssetCondition::from($this->conditionOut),
                $this->checkoutNotes ?: null,
            );
        } catch (AssetAssignmentException $exception) {
            $this->addError('assignment', $exception->getMessage());

            return;
        }

        $this->showCheckout = false;
        $this->reset(['assignableId', 'expectedReturnAt', 'checkoutNotes']);
        session()->flash('status', __('Asset checked out.'));
        $this->refreshAsset();
    }

    public function checkin(AssetAssignmentService $service): void
    {
        $this->validate([
            'conditionIn' => ['required', Rule::in(AssetCondition::values())],
            'checkinNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $assignment = $this->asset->activeAssignment;

        if (! $assignment) {
            $this->addError('assignment', __('This asset has no active assignment.'));

            return;
        }

        try {
            $service->checkin(
                $assignment,
                auth()->user(),
                AssetCondition::from($this->conditionIn),
                $this->checkinNotes ?: null,
            );
        } catch (AssetAssignmentException $exception) {
            $this->addError('assignment', $exception->getMessage());

            return;
        }

        $this->showCheckin = false;
        $this->reset(['checkinNotes']);
        session()->flash('status', __('Asset checked in.'));
        $this->refreshAsset();
    }

    public function delete(): void
    {
        Gate::authorize('delete', $this->asset);

        $tag = $this->asset->asset_tag;
        $this->asset->delete();

        session()->flash('status', __('Asset :tag deleted.', ['tag' => $tag]));

        $this->redirectRoute('assets.index', navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $depreciation = app(\App\Services\DepreciationCalculator::class);

        return [
            'users' => User::orderBy('name')->get(),
            'locations' => Location::orderBy('full_name')->get(),
            'qr' => AssetQrCode::dataUri($this->asset, 200),
            'isDepreciable' => $depreciation->isDepreciable($this->asset),
            'annualDepreciation' => $depreciation->annualAmount($this->asset),
            'accumulatedDepreciation' => $depreciation->accumulated($this->asset),
            'bookValue' => $depreciation->bookValue($this->asset),
            'depreciationSchedule' => $depreciation->schedule($this->asset),
            'activities' => \Spatie\Activitylog\Models\Activity::query()
                ->where('subject_type', $this->asset->getMorphClass())
                ->where('subject_id', $this->asset->getKey())
                ->with('causer')
                ->latest()
                ->take(15)
                ->get(),
        ];
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <x-page-header :title="$asset->name" :subtitle="$asset->asset_tag">
            <x-slot name="actions">
                <x-status-badge :status="$asset->status" />
                @can('update', $asset)
                    <a href="{{ route('assets.edit', $asset) }}" wire:navigate class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        {{ __('Edit') }}
                    </a>
                @endcan
                @can('delete', $asset)
                    <button wire:click="delete" wire:confirm="{{ __('Delete this asset?') }}" class="inline-flex items-center rounded-md border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                        {{ __('Delete') }}
                    </button>
                @endcan
            </x-slot>
        </x-page-header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Overview') }}</h3>

                    @if ($asset->image_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($asset->image_path) }}" class="h-48 w-48 rounded object-cover" alt="">
                    @endif

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500">{{ __('Category') }}</dt>
                            <dd class="text-gray-900">{{ $asset->category?->full_name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('Current location') }}</dt>
                            <dd class="text-gray-900">{{ $asset->location?->full_name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('Condition') }}</dt>
                            <dd class="text-gray-900">{{ $asset->condition?->label() ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('Serial number') }}</dt>
                            <dd class="text-gray-900">{{ $asset->serial_number ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('Manufacturer') }}</dt>
                            <dd class="text-gray-900">{{ $asset->manufacturer ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('Model') }}</dt>
                            <dd class="text-gray-900">{{ $asset->model ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('Supplier') }}</dt>
                            <dd class="text-gray-900">{{ $asset->supplier ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('Added by') }}</dt>
                            <dd class="text-gray-900">{{ $asset->creator?->name ?? '—' }} · {{ $asset->created_at?->format('d M Y') }}</dd>
                        </div>
                    </dl>

                    @if ($asset->description)
                        <div class="border-t border-gray-100 pt-4">
                            <dt class="text-sm text-gray-500">{{ __('Description') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ $asset->description }}</dd>
                        </div>
                    @endif
                </div>

                <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Financials') }}</h3>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500">{{ __('Purchase date') }}</dt>
                            <dd class="text-gray-900">{{ $asset->purchase_date?->format('d M Y') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('Purchase cost') }}</dt>
                            <dd class="text-gray-900">{{ $asset->purchase_cost !== null ? \App\Support\Money::format($asset->purchase_cost, $asset->currency) : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('Salvage value') }}</dt>
                            <dd class="text-gray-900">{{ $asset->salvage_value !== null ? \App\Support\Money::format($asset->salvage_value, $asset->currency) : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('Useful life') }}</dt>
                            <dd class="text-gray-900">{{ $asset->useful_life_years ? $asset->useful_life_years.' '.__('years') : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('Warranty expiry') }}</dt>
                            <dd class="text-gray-900">
                                {{ $asset->warranty_expiry?->format('d M Y') ?? '—' }}
                                @if ($asset->warranty_expiry && $asset->warranty_expiry->isPast())
                                    <span class="ms-1 text-xs text-red-600">{{ __('(expired)') }}</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Depreciation') }}</h3>

                    @if ($isDepreciable)
                        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500">{{ __('Annual depreciation') }}</dt>
                                <dd class="text-gray-900">{{ \App\Support\Money::format($annualDepreciation, $asset->currency) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('Accumulated') }}</dt>
                                <dd class="text-gray-900">{{ \App\Support\Money::format($accumulatedDepreciation, $asset->currency) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('Current book value') }}</dt>
                                <dd class="font-semibold text-gray-900">{{ \App\Support\Money::format($bookValue, $asset->currency) }}</dd>
                            </div>
                        </dl>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Year') }}</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Opening') }}</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Depreciation') }}</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Accumulated') }}</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Closing') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($depreciationSchedule as $row)
                                        <tr>
                                            <td class="px-3 py-1.5 text-gray-700">{{ $row['year'] }}</td>
                                            <td class="px-3 py-1.5 text-right text-gray-600">{{ \App\Support\Money::format($row['opening'], $asset->currency) }}</td>
                                            <td class="px-3 py-1.5 text-right text-gray-600">{{ \App\Support\Money::format($row['depreciation'], $asset->currency) }}</td>
                                            <td class="px-3 py-1.5 text-right text-gray-600">{{ \App\Support\Money::format($row['accumulated'], $asset->currency) }}</td>
                                            <td class="px-3 py-1.5 text-right font-medium text-gray-900">{{ \App\Support\Money::format($row['closing'], $asset->currency) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">{{ __('Add a purchase cost, purchase date, and useful life to calculate depreciation.') }}</p>
                    @endif
                </div>

                @if (! empty($asset->custom_fields))
                    <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Custom fields') }}</h3>

                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            @foreach ($asset->custom_fields as $key => $value)
                                <div>
                                    <dt class="text-gray-500">{{ $key }}</dt>
                                    <dd class="text-gray-900">{{ $value !== '' ? $value : '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif

                <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Assignment history') }}</h3>

                    @forelse ($asset->assignments as $assignment)
                        <div class="border-l-2 border-gray-200 pl-4 py-1" wire:key="assignment-{{ $assignment->id }}">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $assignment->assignable?->name ?? __('Unknown') }}
                                    <span class="text-xs font-normal text-gray-400">
                                        ({{ $assignment->assignable_type === \App\Models\User::class ? __('User') : __('Location') }})
                                    </span>
                                </p>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $assignment->status->color() }}">
                                    {{ $assignment->status->label() }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500">
                                {{ __('Out') }}: {{ $assignment->assigned_at?->format('d M Y') }}
                                @if ($assignment->expected_return_at)
                                    · {{ __('Due') }}: {{ $assignment->expected_return_at->format('d M Y') }}
                                @endif
                                @if ($assignment->returned_at)
                                    · {{ __('In') }}: {{ $assignment->returned_at->format('d M Y') }}
                                @endif
                            </p>
                            @if ($assignment->checkout_notes)
                                <p class="text-xs text-gray-500">{{ $assignment->checkout_notes }}</p>
                            @endif
                            @if ($assignment->checkin_notes)
                                <p class="text-xs text-gray-500">{{ __('Returned') }}: {{ $assignment->checkin_notes }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">{{ __('No assignments yet.') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white shadow sm:rounded-lg p-6 space-y-3">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Assignment') }}</h3>

                    @error('assignment')
                        <div class="rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $message }}</div>
                    @enderror

                    @php($active = $asset->activeAssignment)

                    @if ($active)
                        <div class="text-sm space-y-1">
                            <p class="text-gray-500">{{ __('Currently with') }}</p>
                            <p class="font-medium text-gray-900">{{ $active->assignable?->name ?? __('Unknown') }}</p>
                            <p class="text-xs text-gray-500">{{ __('Since') }} {{ $active->assigned_at?->format('d M Y') }}</p>
                            @if ($active->expected_return_at)
                                <p class="text-xs {{ $active->isOverdue() ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                                    {{ __('Due') }} {{ $active->expected_return_at->format('d M Y') }}
                                    @if ($active->isOverdue()) ({{ __('overdue') }}) @endif
                                </p>
                            @endif
                        </div>

                        @if ($showCheckin)
                            <form wire:submit="checkin" class="space-y-3 border-t border-gray-100 pt-3">
                                <div>
                                    <x-input-label for="conditionIn" :value="__('Condition on return')" />
                                    <select wire:model="conditionIn" id="conditionIn" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        @foreach (\App\Enums\AssetCondition::cases() as $condition)
                                            <option value="{{ $condition->value }}">{{ $condition->label() }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('conditionIn')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="checkinNotes" :value="__('Notes')" />
                                    <textarea wire:model="checkinNotes" id="checkinNotes" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                                </div>
                                <div class="flex items-center gap-3">
                                    <x-primary-button>{{ __('Check in') }}</x-primary-button>
                                    <button type="button" wire:click="cancelCheckin" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</button>
                                </div>
                            </form>
                        @else
                            <button wire:click="openCheckin" class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                                {{ __('Check in') }}
                            </button>
                        @endif
                    @else
                        <p class="text-sm text-gray-500">{{ __('This asset is available for checkout.') }}</p>

                        @if ($showCheckout)
                            <form wire:submit="checkout" class="space-y-3 border-t border-gray-100 pt-3">
                                <div>
                                    <x-input-label for="assignableType" :value="__('Assign to')" />
                                    <select wire:model.live="assignableType" id="assignableType" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="user">{{ __('A person') }}</option>
                                        <option value="location">{{ __('A location') }}</option>
                                    </select>
                                </div>

                                <div>
                                    <x-input-label for="assignableId" :value="$assignableType === 'user' ? __('Person') : __('Location')" />
                                    <select wire:model="assignableId" id="assignableId" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="">{{ __('— Select —') }}</option>
                                        @foreach ($assignableType === 'user' ? $users : $locations as $option)
                                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('assignableId')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="expectedReturnAt" :value="__('Expected return')" />
                                    <x-text-input wire:model="expectedReturnAt" id="expectedReturnAt" type="date" class="mt-1 block w-full" />
                                    <x-input-error :messages="$errors->get('expectedReturnAt')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="conditionOut" :value="__('Condition out')" />
                                    <select wire:model="conditionOut" id="conditionOut" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        @foreach (\App\Enums\AssetCondition::cases() as $condition)
                                            <option value="{{ $condition->value }}">{{ $condition->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <x-input-label for="checkoutNotes" :value="__('Notes')" />
                                    <textarea wire:model="checkoutNotes" id="checkoutNotes" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                                </div>

                                <div class="flex items-center gap-3">
                                    <x-primary-button>{{ __('Check out') }}</x-primary-button>
                                    <button type="button" wire:click="cancelCheckout" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</button>
                                </div>
                            </form>
                        @else
                            <button wire:click="openCheckout" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                {{ __('Check out') }}
                            </button>
                        @endif
                    @endif
                </div>

                <div class="bg-white shadow sm:rounded-lg p-6 text-center">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('QR label') }}</h3>
                    <img src="{{ $qr }}" class="mx-auto mt-3 h-40 w-40" alt="{{ $asset->asset_tag }}">
                    <p class="mt-2 font-mono text-xs text-gray-500">{{ $asset->asset_tag }}</p>
                    <a href="{{ route('labels.single', $asset) }}" target="_blank" class="mt-3 inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                        {{ __('Print label') }}
                    </a>
                </div>

                <div class="bg-white shadow sm:rounded-lg p-6">
                    <livewire:attachments.panel :attachable="$asset" wire:key="attachments-asset-{{ $asset->id }}" />
                </div>

                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Activity') }}</h3>
                    <ul class="mt-3 space-y-2">
                        @forelse ($activities as $activity)
                            <li class="text-xs text-gray-600" wire:key="asset-activity-{{ $activity->id }}">
                                <span class="font-medium text-gray-800">{{ ucfirst($activity->event) }}</span>
                                — {{ $activity->created_at?->format('d M Y H:i') }}
                                @if ($activity->causer)
                                    · {{ $activity->causer->name }}
                                @endif
                            </li>
                        @empty
                            <li class="text-sm text-gray-500">{{ __('No changes recorded yet.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <livewire:maintenance.panel :asset="$asset" wire:key="maintenance-panel-{{ $asset->id }}" />
        </div>
    </div>
</div>
