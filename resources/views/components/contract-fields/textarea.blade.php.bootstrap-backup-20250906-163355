@props(['field', 'value' => null, 'errors' => []])

@php
    $fieldSlug = $field['field_slug'];
    $hasError = !empty($errors[$fieldSlug]);
    $uiConfig = $field['ui_config'] ?? [];
    $textareaClass = 'form-control' . ($hasError ? ' is-invalid' : '');
    
    // Extract UI configuration
    $rows = $uiConfig['rows'] ?? 4;
    $maxlength = $uiConfig['maxlength'] ?? null;
    $minlength = $uiConfig['minlength'] ?? null;
    $resize = $uiConfig['resize'] ?? 'vertical'; // none, vertical, horizontal, both
    $showCharCount = $uiConfig['show_char_count'] ?? false;
    $wysiwyg = $uiConfig['wysiwyg'] ?? false; // Rich text editor
    $placeholder = $field['placeholder'] ?? 'Enter text...';
@endphp

<div class="form-group mb-3">
    <label for="{{ $fieldSlug }}" class="form-label">
        {{ $field['label'] }}
        @if($field['is_required'])
            <span class="text-danger">*</span>
        @endif
    </label>
    
    @if($wysiwyg)
        <div class="wysiwyg-container">
            <div id="{{ $fieldSlug }}_toolbar" class="wysiwyg-toolbar">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-command="bold" title="Bold">
                    <i class="fas fa-bold"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-command="italic" title="Italic">
                    <i class="fas fa-italic"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-command="underline" title="Underline">
                    <i class="fas fa-underline"></i>
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" title="Format">
                        <i class="fas fa-heading"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" data-command="formatBlock" data-value="p">Normal</a></li>
                        <li><a class="dropdown-item" data-command="formatBlock" data-value="h1">Heading 1</a></li>
                        <li><a class="dropdown-item" data-command="formatBlock" data-value="h2">Heading 2</a></li>
                        <li><a class="dropdown-item" data-command="formatBlock" data-value="h3">Heading 3</a></li>
                    </ul>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-command="insertUnorderedList" title="Bullet List">
                    <i class="fas fa-list-ul"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-command="insertOrderedList" title="Numbered List">
                    <i class="fas fa-list-ol"></i>
                </button>
            </div>
            
            <div 
                id="{{ $fieldSlug }}_editor"
                class="{{ $textareaClass }} wysiwyg-editor"
                contenteditable="true"
                style="min-height: {{ $rows * 1.5 }}rem; resize: {{ $resize }};"
                data-placeholder="{{ $placeholder }}"
                @if($maxlength) data-maxlength="{{ $maxlength }}" @endif
            >
                {!! old($fieldSlug, $value) !!}
            </div>
            
            <textarea 
                id="{{ $fieldSlug }}"
                name="{{ $fieldSlug }}"
                class="d-none"
                @if($field['is_required']) required @endif
            >{{ old($fieldSlug, $value) }}</textarea>
        </div>
    @else
        <textarea 
            id="{{ $fieldSlug }}"
            name="{{ $fieldSlug }}"
            class="{{ $textareaClass }}"
            rows="{{ $rows }}"
            placeholder="{{ $placeholder }}"
            @if($field['is_required']) required @endif
            @if($maxlength) maxlength="{{ $maxlength }}" @endif
            @if($minlength) minlength="{{ $minlength }}" @endif
            style="resize: {{ $resize }};"
        >{{ old($fieldSlug, $value) }}</textarea>
    @endif
    
    @if($showCharCount && $maxlength)
        <small class="form-text text-muted d-flex justify-content-between">
            <span>{{ $field['help_text'] ?? '' }}</span>
            <span class="char-count">
                <span id="{{ $fieldSlug }}_count">{{ strlen(old($fieldSlug, $value ?? '')) }}</span>/{{ $maxlength }}
            </span>
        </small>
    @elseif($field['help_text'])
        <small class="form-text text-muted">{{ $field['help_text'] }}</small>
    @endif
    
    @if($hasError)
        <div class="invalid-feedback">
            @foreach($errors[$fieldSlug] as $error)
                {{ $error }}
            @endforeach
        </div>
    @endif
</div>

@push('styles')
<style>
    .wysiwyg-toolbar {
        border: 1px solid #ced4da;
        border-bottom: none;
        background: #f8f9fa;
        padding: 0.5rem;
        border-top-left-radius: 0.375rem;
        border-top-right-radius: 0.375rem;
    }
    
    .wysiwyg-editor {
        border-top-left-radius: 0;
        border-top-right-radius: 0;
        min-height: 6rem;
    }
    
    .wysiwyg-editor:empty:before {
        content: attr(data-placeholder);
        color: #6c757d;
        font-style: italic;
    }
    
    .wysiwyg-editor:focus {
        outline: none;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const textarea = document.getElementById('{{ $fieldSlug }}');
        
        @if($wysiwyg)
            const editor = document.getElementById('{{ $fieldSlug }}_editor');
            const toolbar = document.getElementById('{{ $fieldSlug }}_toolbar');
            
            // Sync editor content with hidden textarea
            function syncContent() {
                textarea.value = editor.innerHTML;
            }
            
            editor.addEventListener('input', syncContent);
            editor.addEventListener('blur', syncContent);
            
            // Handle toolbar buttons
            toolbar.addEventListener('click', function(e) {
                if (e.target.hasAttribute('data-command')) {
                    e.preventDefault();
                    const command = e.target.getAttribute('data-command');
                    const value = e.target.getAttribute('data-value') || null;
                    
                    document.execCommand(command, false, value);
                    syncContent();
                } else if (e.target.closest('[data-command]')) {
                    e.preventDefault();
                    const button = e.target.closest('[data-command]');
                    const command = button.getAttribute('data-command');
                    const value = button.getAttribute('data-value') || null;
                    
                    document.execCommand(command, false, value);
                    syncContent();
                }
            });
            
            @if($maxlength)
                editor.addEventListener('input', function() {
                    const content = this.textContent || this.innerText || '';
                    if (content.length > {{ $maxlength }}) {
                        // Prevent further input if max length exceeded
                        const selection = window.getSelection();
                        const range = selection.getRangeAt(0);
                        this.innerHTML = this.innerHTML.substring(0, {{ $maxlength }});
                        range.setStart(this.firstChild, Math.min(range.startOffset, {{ $maxlength }}));
                        range.collapse(true);
                        selection.removeAllRanges();
                        selection.addRange(range);
                    }
                });
            @endif
        @endif
        
        @if($showCharCount && $maxlength)
            const charCountElement = document.getElementById('{{ $fieldSlug }}_count');
            
            function updateCharCount() {
                @if($wysiwyg)
                    const content = document.getElementById('{{ $fieldSlug }}_editor').textContent || '';
                @else
                    const content = textarea.value;
                @endif
                charCountElement.textContent = content.length;
                
                // Update color based on character count
                const percentage = (content.length / {{ $maxlength }}) * 100;
                if (percentage > 90) {
                    charCountElement.className = 'text-danger';
                } else if (percentage > 75) {
                    charCountElement.className = 'text-warning';
                } else {
                    charCountElement.className = 'text-muted';
                }
            }
            
            @if($wysiwyg)
                document.getElementById('{{ $fieldSlug }}_editor').addEventListener('input', updateCharCount);
            @else
                textarea.addEventListener('input', updateCharCount);
            @endif
        @endif
        
        @unless($wysiwyg)
            // Auto-resize textarea based on content
            function autoResize() {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            }
            
            textarea.addEventListener('input', autoResize);
            
            // Initial resize
            autoResize();
        @endunless
    });
</script>
@endpush