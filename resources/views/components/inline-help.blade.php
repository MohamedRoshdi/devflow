@props([
    'icon' => 'ℹ️',
    'brief' => '',
    'details' => [],
    'docsLink' => '#',
    'helpTopic' => '',
    'collapsible' => false,
])

<div class="help-text mt-2" role="complementary" aria-label="Help information">
    @if($collapsible)
        <details class="help-details">
            <summary class="help-summary cursor-pointer" role="button" aria-expanded="false">
                <span class="help-text-icon" aria-hidden="true">{{ $icon }}</span>
                <strong>{{ $brief }}</strong>
            </summary>
            <div class="help-content mt-2 ms-4">
                @foreach($details as $label => $value)
                    <div class="help-item">
                        <span class="text-muted" aria-hidden="true">•</span>
                        <span class="text-muted">{{ $label }}:</span>
                        <span>{{ $value }}</span>
                    </div>
                @endforeach

                @if($docsLink !== '#')
                    <div class="mt-2">
                        <a href="{{ $docsLink }}"
                           class="text-primary text-decoration-none hover:underline transition-all"
                           target="_blank"
                           rel="noopener noreferrer"
                           aria-label="Learn more (opens in new window)">
                            <span aria-hidden="true">📚</span> Learn more →
                        </a>
                    </div>
                @endif

                @if($helpTopic)
                    <div class="mt-2">
                        <a href="#"
                           wire:click.prevent="showHelp('{{ $helpTopic }}')"
                           class="text-primary text-decoration-none hover:underline transition-all"
                           aria-label="View detailed guide">
                            <span aria-hidden="true">📖</span> View detailed guide →
                        </a>
                    </div>
                @endif
            </div>
        </details>
    @else
        <div class="help-content">
            <div class="help-brief mb-1">
                <span class="help-text-icon" aria-hidden="true">{{ $icon }}</span>
                <strong>{{ $brief }}</strong>
            </div>
            <div class="help-details ms-4">
                @foreach($details as $label => $value)
                    <div class="help-item text-muted">
                        <span aria-hidden="true">•</span>
                        <span>{{ $label }}:</span>
                        <span class="text-secondary">{{ $value }}</span>
                    </div>
                @endforeach
            </div>

            @if($docsLink !== '#' || $helpTopic)
                <div class="help-links ms-4 mt-1">
                    @if($docsLink !== '#')
                        <a href="{{ $docsLink }}"
                           class="text-primary text-decoration-none me-3 hover:underline transition-all"
                           target="_blank"
                           rel="noopener noreferrer"
                           aria-label="Learn more (opens in new window)">
                            <span aria-hidden="true">📚</span> Learn more →
                        </a>
                    @endif

                    @if($helpTopic)
                        <a href="#"
                           wire:click.prevent="$dispatch('show-help-modal', { topic: '{{ $helpTopic }}' })"
                           class="text-primary text-decoration-none hover:underline transition-all"
                           aria-label="View detailed guide">
                            <span aria-hidden="true">📖</span> View detailed guide →
                        </a>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>

<style>
    .help-text {
        font-size: 0.875rem;
        line-height: 1.6;
    }

    .help-text-icon {
        font-size: 1rem;
        margin-right: 0.25rem;
    }

    .help-item {
        margin-bottom: 0.25rem;
    }

    .help-summary {
        list-style: none;
        cursor: pointer;
        user-select: none;
        padding: 0.5rem;
        border-radius: 0.25rem;
        transition: background-color 0.2s;
    }

    .help-summary:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }

    .dark .help-summary:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }

    .help-summary::-webkit-details-marker {
        display: none;
    }

    .help-summary::before {
        content: '▶';
        display: inline-block;
        margin-right: 0.5rem;
        transition: transform 0.2s;
        font-size: 0.75rem;
    }

    details[open] .help-summary::before {
        transform: rotate(90deg);
    }

    .help-links a {
        display: inline-block;
        transition: transform 0.2s;
    }

    .help-links a:hover {
        transform: translateX(3px);
    }
</style>
