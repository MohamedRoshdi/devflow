<div>
@if($helpContent)
    <div class="inline-help mt-2"
         wire:key="help-{{ $helpContent->key }}"
         x-data="{ expanded: @entangle('showDetails') }"
         role="complementary"
         aria-label="Help content">

        @if($collapsible)
            <!-- Collapsible Version -->
            <div class="help-toggle"
                 @click="expanded = !expanded"
                 role="button"
                 tabindex="0"
                 aria-expanded="expanded"
                 aria-controls="help-details-{{ $helpContent->key }}"
                 @keydown.enter="expanded = !expanded"
                 @keydown.space.prevent="expanded = !expanded">
                <span class="help-icon" aria-hidden="true">{{ $helpContent->icon }}</span>
                <strong class="help-title">{{ $helpContent->getLocalizedBrief() }}</strong>
                <span class="toggle-indicator ms-2" x-show="!expanded" aria-hidden="true">▼</span>
                <span class="toggle-indicator ms-2" x-show="expanded" aria-hidden="true">▲</span>
            </div>

            <div id="help-details-{{ $helpContent->key }}"
                 class="help-details ms-4 mt-2"
                 x-show="expanded"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95">
                @include('components.help-details')
            </div>
        @else
            <!-- Always Visible Version -->
            <div class="help-content">
                <div class="help-brief mb-1">
                    <span class="help-icon" aria-hidden="true">{{ $helpContent->icon }}</span>
                    <strong>{{ $helpContent->getLocalizedBrief() }}</strong>
                </div>

                <div class="help-details ms-4">
                    @include('components.help-details')
                </div>
            </div>
        @endif

        <!-- Feedback Buttons -->
        <div class="help-feedback ms-4 mt-2" role="group" aria-label="Help feedback">
            <small class="text-muted me-2">Was this helpful?</small>
            <button wire:click="markHelpful"
                    class="btn btn-sm btn-outline-success"
                    aria-label="Mark as helpful"
                    title="Yes, helpful"
                    @disabled($isLoading)>
                @if($isLoading)
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                @else
                    <span aria-hidden="true">👍</span>
                @endif
            </button>
            <button wire:click="markNotHelpful"
                    class="btn btn-sm btn-outline-danger"
                    aria-label="Mark as not helpful"
                    title="No, not helpful"
                    @disabled($isLoading)>
                @if($isLoading)
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                @else
                    <span aria-hidden="true">👎</span>
                @endif
            </button>
        </div>

        <!-- Related Help (if expanded and available) -->
        @if($showDetails && $relatedHelp->isNotEmpty())
            <div class="related-help ms-4 mt-3 p-2 bg-light border-start border-primary border-3 dark:bg-gray-800"
                 x-show="expanded"
                 x-transition:enter="transition ease-out duration-300 delay-100"
                 x-transition:enter-start="opacity-0 transform translateY(-10px)"
                 x-transition:enter-end="opacity-100 transform translateY(0)"
                 role="complementary"
                 aria-label="Related help topics">
                <small class="text-muted d-block mb-2">
                    <strong>Related Help:</strong>
                </small>
                @foreach($relatedHelp as $related)
                    <div class="related-item mb-1">
                        <a href="#"
                           wire:click.prevent="$dispatch('show-help', { key: '{{ $related->key }}' })"
                           class="text-primary text-decoration-none"
                           aria-label="View related help: {{ $related->title }}">
                            <span aria-hidden="true">{{ $related->icon }}</span> {{ $related->title }}
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Loading State Indicator -->
        @if($isLoading)
            <div class="help-loading ms-4 mt-2" role="status">
                <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                <small class="text-muted">Processing feedback...</small>
            </div>
        @endif
    </div>
@else
    <!-- Fallback if help content not found - empty for cleaner UI -->
@endif
</div>
