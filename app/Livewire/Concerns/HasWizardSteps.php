<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

/**
 * Reusable trait for multi-step wizard forms in Livewire components.
 *
 * Components using this trait must define:
 * - $totalSteps: int - Total number of steps
 * - validateStep(int $step): void - Step-specific validation
 */
trait HasWizardSteps
{
    public int $currentStep = 1;

    /**
     * Move to the next step after validating current step
     */
    public function nextStep(): void
    {
        $this->validateStep($this->currentStep);

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    /**
     * Move to the previous step
     */
    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    /**
     * Jump to a specific step (only backwards or current)
     */
    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= $this->totalSteps && $step <= $this->currentStep) {
            $this->currentStep = $step;
        }
    }

    /**
     * Check if current step is the first step
     */
    public function isFirstStep(): bool
    {
        return $this->currentStep === 1;
    }

    /**
     * Check if current step is the last step
     */
    public function isLastStep(): bool
    {
        return $this->currentStep === $this->totalSteps;
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(): int
    {
        return (int) (($this->currentStep / $this->totalSteps) * 100);
    }

    /**
     * Check if a step has been completed
     */
    public function isStepCompleted(int $step): bool
    {
        return $step < $this->currentStep;
    }

    /**
     * Check if a step is the current step
     */
    public function isCurrentStep(int $step): bool
    {
        return $step === $this->currentStep;
    }

    /**
     * Check if a step is accessible (can be navigated to)
     */
    public function isStepAccessible(int $step): bool
    {
        return $step <= $this->currentStep;
    }

    /**
     * Reset wizard to first step
     */
    public function resetWizard(): void
    {
        $this->currentStep = 1;
    }

    /**
     * Validate a specific step - must be implemented by the component
     */
    abstract protected function validateStep(int $step): void;
}
