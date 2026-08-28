<?php

use App\Concerns\ManagesPageContent;
use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] #[Title('Services')] class extends Component {
    use ManagesPageContent;
    use WithFileUploads;

    public function mount(): void
    {
        $this->resetForm();
    }

    public function pageType(): string
    {
        return Page::TYPE_SERVICE;
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">Services</flux:heading>
        <flux:text class="mt-1">Create and edit the service pages shown in the site navigation.</flux:text>
    </div>

    <x-admin.confirm-modal :pending="$pendingAction" />

    <x-admin.page-editor :pages="$this->pages" :editing-id="$editingId" :sections="$sections" :slug="$slug" label="Service" :photo="$photo"
        :gallery-image="$galleryImage" :current-image="$currentImage"
        :picking-from-gallery="$pickingFromGallery" :choices="$this->galleryChoices"
        :collections="$this->galleryCollections" :gallery-collection="$galleryCollection" />
</div>
