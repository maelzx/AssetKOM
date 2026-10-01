<?php

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $attachableType = '';

    public int $attachableId = 0;

    public mixed $file = null;

    public function mount(Model $attachable): void
    {
        $this->attachableType = $attachable->getMorphClass();
        $this->attachableId = $attachable->getKey();
    }

    protected function attachable(): Model
    {
        return $this->attachableType::findOrFail($this->attachableId);
    }

    public function upload(): void
    {
        Gate::authorize('create', Attachment::class);

        $this->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,csv,txt,png,jpg,jpeg,gif,zip'],
        ]);

        $path = $this->file->store('attachments', 'local');

        $this->attachable()->attachments()->create([
            'original_name' => $this->file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $this->file->getClientMimeType(),
            'size' => $this->file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        $this->reset('file');
        $this->dispatch('attachment-updated');
    }

    public function delete(int $id): void
    {
        $attachment = $this->attachable()->attachments()->findOrFail($id);

        Gate::authorize('delete', $attachment);

        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();

        $this->dispatch('attachment-updated');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'attachments' => $this->attachable()->attachments()->with('uploader')->latest()->get(),
        ];
    }
}; ?>

<div class="space-y-3">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">{{ __('Attachments') }}</h3>
        <span class="text-xs text-gray-400">{{ trans_choice(':count file|:count files', $attachments->count()) }}</span>
    </div>

    <form wire:submit="upload" class="flex flex-wrap items-center gap-3">
        <input wire:model="file" type="file" class="text-sm text-gray-600" />
        <x-primary-button>{{ __('Upload') }}</x-primary-button>
        <x-action-message on="attachment-updated">{{ __('Saved.') }}</x-action-message>
        <x-input-error :messages="$errors->get('file')" class="w-full" />
    </form>

    <ul class="divide-y divide-gray-100">
        @forelse ($attachments as $attachment)
            <li class="flex items-center justify-between gap-2 py-2" wire:key="attachment-{{ $attachment->id }}">
                <div class="min-w-0">
                    <a href="{{ route('attachments.download', $attachment) }}" class="text-sm text-indigo-600 hover:text-indigo-900 break-all">
                        {{ $attachment->original_name }}
                    </a>
                    <p class="text-xs text-gray-400">
                        {{ $attachment->humanSize() }}
                        @if ($attachment->uploader) · {{ $attachment->uploader->name }} @endif
                        · {{ $attachment->created_at?->format('d M Y') }}
                    </p>
                </div>
                @can('delete', $attachment)
                    <button wire:click="delete({{ $attachment->id }})" wire:confirm="{{ __('Delete this attachment?') }}" class="text-xs text-red-600 hover:text-red-900">
                        {{ __('Delete') }}
                    </button>
                @endcan
            </li>
        @empty
            <li class="py-2 text-sm text-gray-500">{{ __('No attachments.') }}</li>
        @endforelse
    </ul>
</div>
