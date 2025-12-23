@foreach($helpContent->getLocalizedDetails() as $label => $value)
    <div class="help-item text-muted small mb-1">
        <span aria-hidden="true">•</span>
        <span class="fw-semibold">{{ $label }}:</span>
        <span class="text-secondary">{{ $value }}</span>
    </div>
@endforeach

@if($helpContent->docs_url)
    <div class="help-links mt-2">
        <a href="{{ $helpContent->docs_url }}"
           target="_blank"
           rel="noopener noreferrer"
           class="text-primary text-decoration-none small hover:underline transition-all"
           aria-label="Read full documentation (opens in new window)">
            <span aria-hidden="true">📚</span> Read full documentation →
        </a>
    </div>
@endif

@if($helpContent->video_url)
    <div class="help-links mt-1">
        <a href="{{ $helpContent->video_url }}"
           target="_blank"
           rel="noopener noreferrer"
           class="text-primary text-decoration-none small hover:underline transition-all"
           aria-label="Watch video tutorial (opens in new window)">
            <span aria-hidden="true">🎥</span> Watch video tutorial →
        </a>
    </div>
@endif
