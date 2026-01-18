<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

/**
 * Reusable trait for modal state management in Livewire components.
 *
 * Provides standardized create, edit, and delete modal handling with
 * automatic form reset callbacks and ID tracking for edit/delete operations.
 *
 * Usage:
 *   use WithModalManagement;
 *
 *   // Override resetModalForm() to clear your form fields
 *   protected function resetModalForm(): void {
 *       $this->name = '';
 *       $this->email = '';
 *   }
 */
trait WithModalManagement
{
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    // ===== CREATE MODAL =====

    /**
     * Open the create modal
     */
    public function openCreateModal(): void
    {
        $this->resetModalForm();
        $this->showCreateModal = true;
    }

    /**
     * Close the create modal
     */
    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetModalForm();
    }

    // ===== EDIT MODAL =====

    /**
     * Open the edit modal for a specific item
     */
    public function openEditModal(int $id): void
    {
        $this->editingId = $id;
        $this->loadEditData($id);
        $this->showEditModal = true;
    }

    /**
     * Close the edit modal
     */
    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingId = null;
        $this->resetModalForm();
    }

    // ===== DELETE MODAL =====

    /**
     * Open the delete confirmation modal for a specific item
     */
    public function openDeleteModal(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    /**
     * Close the delete confirmation modal
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    // ===== HELPER METHODS =====

    /**
     * Close all modals at once
     */
    public function closeAllModals(): void
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showDeleteModal = false;
        $this->editingId = null;
        $this->deletingId = null;
        $this->resetModalForm();
    }

    /**
     * Check if any modal is currently open
     */
    public function hasOpenModal(): bool
    {
        return $this->showCreateModal || $this->showEditModal || $this->showDeleteModal;
    }

    /**
     * Reset form fields when closing modals.
     *
     * Override this method in your component to reset form-specific fields.
     */
    protected function resetModalForm(): void
    {
        // Override in component to reset form fields
        // Example:
        // $this->name = '';
        // $this->email = '';
    }

    /**
     * Load data for editing.
     *
     * Override this method in your component to load the item being edited.
     */
    protected function loadEditData(int $id): void
    {
        // Override in component to load edit data
        // Example:
        // $item = Model::findOrFail($id);
        // $this->name = $item->name;
    }
}
