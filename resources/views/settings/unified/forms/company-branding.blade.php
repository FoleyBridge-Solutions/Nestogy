<div class="space-y-8">
    <flux:callout icon="sparkles" variant="info">
        <flux:heading size="sm">Customize Your Brand</flux:heading>
        <flux:text size="sm" class="mt-1">Make this application your own by uploading your logo and choosing colors that match your brand identity. All changes apply instantly across the entire platform.</flux:text>
    </flux:callout>

    <flux:card>
        <div class="flex items-start justify-between mb-4">
            <div>
                <flux:heading size="lg">Logos</flux:heading>
                <flux:text variant="muted">Upload your company logos for light and dark modes</flux:text>
            </div>
            <flux:badge color="blue" icon="information-circle">Optional</flux:badge>
        </div>
        
        <div class="space-y-6">
            <flux:field>
                <div class="flex items-center justify-between mb-2">
                    <flux:label>Light Mode Logo</flux:label>
                    <flux:text size="xs" variant="muted">PNG, SVG • Max 200KB</flux:text>
                </div>
                <flux:input type="url" name="logo_url" value="{{ $settings['logo_url'] ?? '' }}" placeholder="https://cdn.example.com/logo.png" />
                <flux:text size="sm" variant="muted">
                    <flux:icon name="arrow-up-tray" variant="micro" class="inline" />
                    Upload your logo to a CDN or image host, then paste the URL here
                </flux:text>
            </flux:field>

            @if($settings['logo_url'] ?? false)
                <div class="p-4 border rounded-lg bg-zinc-50 dark:bg-zinc-900">
                    <flux:text size="sm" variant="muted" class="mb-3 flex items-center gap-2">
                        <flux:icon name="eye" variant="micro" />
                        Light Mode Preview:
                    </flux:text>
                    <flux:brand href="#" 
                                logo="{{ $settings['logo_url'] }}" 
                                name="{{ Auth::user()?->company?->name ?? 'Company Name' }}" />
                </div>
            @endif

            <flux:separator />

            <flux:field>
                <div class="flex items-center justify-between mb-2">
                    <flux:label>Dark Mode Logo <flux:badge size="sm" color="zinc">Optional</flux:badge></flux:label>
                    <flux:text size="xs" variant="muted">PNG, SVG • Max 200KB</flux:text>
                </div>
                <flux:input type="url" name="logo_dark_url" value="{{ $settings['logo_dark_url'] ?? '' }}" placeholder="https://cdn.example.com/logo-dark.png" />
                <flux:text size="sm" variant="muted">Leave empty to use the same logo in dark mode</flux:text>
            </flux:field>

            @if($settings['logo_dark_url'] ?? false)
                <div class="p-4 border rounded-lg bg-zinc-900">
                    <flux:text size="sm" class="mb-3 flex items-center gap-2 text-zinc-400">
                        <flux:icon name="eye" variant="micro" />
                        Dark Mode Preview:
                    </flux:text>
                    <flux:brand href="#" 
                                logo="{{ $settings['logo_dark_url'] }}" 
                                name="{{ Auth::user()?->company?->name ?? 'Company Name' }}" 
                                class="text-white" />
                </div>
            @endif

            <flux:separator />

            <flux:field>
                <div class="flex items-center justify-between mb-2">
                    <flux:label>Favicon <flux:badge size="sm" color="zinc">Optional</flux:badge></flux:label>
                    <flux:text size="xs" variant="muted">ICO, PNG • 32x32px</flux:text>
                </div>
                <flux:input type="url" name="favicon_url" value="{{ $settings['favicon_url'] ?? '' }}" placeholder="https://cdn.example.com/favicon.ico" />
                <flux:text size="sm" variant="muted">
                    <flux:icon name="globe-alt" variant="micro" class="inline" />
                    Small icon shown in browser tabs • Recommended: 32x32 or 16x16 pixels
                </flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <div class="flex items-start justify-between mb-4">
            <div>
                <flux:heading size="lg">Brand Colors</flux:heading>
                <flux:text variant="muted">Choose colors that represent your brand</flux:text>
            </div>
        </div>
        
        <flux:callout icon="paint-brush" variant="tip" class="mb-6">
            <flux:text size="sm">
                <strong>Quick Tip:</strong> Click the color picker to visually choose your brand color. The system automatically generates complementary shades for the best visual experience.
            </flux:text>
        </flux:callout>
        
        <div class="space-y-6">
            <flux:field>
                <flux:label>Primary Brand Color</flux:label>
                <div class="flex gap-3 items-center">
                    <input type="color" 
                           id="accent_color_picker"
                           value="{{ $settings['accent_color'] ?? '#3b82f6' }}" 
                           class="h-12 w-16 rounded-lg border-2 border-zinc-300 dark:border-zinc-700 cursor-pointer"
                           title="Click to choose a color"
                           onchange="document.getElementById('accent_color_input').value = this.value">
                    <div class="flex-1">
                        <flux:input id="accent_color_input"
                                    name="accent_color" 
                                    value="{{ $settings['accent_color'] ?? '#3b82f6' }}" 
                                    placeholder="#3b82f6"
                                    onchange="document.getElementById('accent_color_picker').value = this.value" />
                        <flux:text size="sm" variant="muted" class="mt-1">
                            Used for buttons, links, and highlights
                        </flux:text>
                    </div>
                </div>
            </flux:field>

            <flux:separator />

            <details class="group">
                <summary class="cursor-pointer list-none">
                    <div class="flex items-center gap-2 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                        <flux:icon name="chevron-right" variant="micro" class="group-open:rotate-90 transition-transform" />
                        <flux:text class="font-medium">Advanced Color Options</flux:text>
                        <flux:badge size="sm" color="zinc">Optional</flux:badge>
                    </div>
                </summary>
                
                <div class="mt-4 space-y-6 pl-6">
                    <flux:text size="sm" variant="muted">Fine-tune your color palette for better contrast and readability</flux:text>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <flux:field>
                            <flux:label>Text Color Shade</flux:label>
                            <div class="flex gap-3 items-center">
                                <input type="color" 
                                       id="accent_content_color_picker"
                                       value="{{ $settings['accent_content_color'] ?? '#2563eb' }}" 
                                       class="h-12 w-16 rounded-lg border-2 border-zinc-300 dark:border-zinc-700 cursor-pointer"
                                       onchange="document.getElementById('accent_content_color_input').value = this.value">
                                <flux:input id="accent_content_color_input"
                                            name="accent_content_color" 
                                            value="{{ $settings['accent_content_color'] ?? '#2563eb' }}" 
                                            placeholder="#2563eb"
                                            onchange="document.getElementById('accent_content_color_picker').value = this.value" />
                            </div>
                            <flux:text size="sm" variant="muted">Slightly darker for better text readability</flux:text>
                        </flux:field>

                        <flux:field>
                            <flux:label>Button Text Color</flux:label>
                            <div class="flex gap-3 items-center">
                                <input type="color" 
                                       id="accent_foreground_color_picker"
                                       value="{{ $settings['accent_foreground_color'] ?? '#ffffff' }}" 
                                       class="h-12 w-16 rounded-lg border-2 border-zinc-300 dark:border-zinc-700 cursor-pointer"
                                       onchange="document.getElementById('accent_foreground_color_input').value = this.value">
                                <flux:input id="accent_foreground_color_input"
                                            name="accent_foreground_color" 
                                            value="{{ $settings['accent_foreground_color'] ?? '#ffffff' }}" 
                                            placeholder="#ffffff"
                                            onchange="document.getElementById('accent_foreground_color_picker').value = this.value" />
                            </div>
                            <flux:text size="sm" variant="muted">Text color on colored buttons (usually white)</flux:text>
                        </flux:field>
                    </div>
                </div>
            </details>

            <flux:separator />

            <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-4">
                <flux:text size="sm" variant="muted" class="mb-3 flex items-center gap-2">
                    <flux:icon name="eye" variant="micro" />
                    Preview:
                </flux:text>
                <div class="flex flex-wrap gap-3">
                    <flux:button variant="primary">Primary Button</flux:button>
                    <flux:button variant="ghost">Ghost Button</flux:button>
                    <flux:badge>Badge</flux:badge>
                    <flux:link href="#">Sample Link</flux:link>
                </div>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <div class="flex items-start justify-between mb-4">
            <div>
                <flux:heading size="lg">Gray Tone & Theme</flux:heading>
                <flux:text variant="muted">Subtle appearance preferences</flux:text>
            </div>
            <flux:badge color="zinc" icon="adjustments-horizontal">Advanced</flux:badge>
        </div>
        
        <div class="space-y-6">
            <flux:field>
                <flux:label>Gray Color Tone</flux:label>
                <flux:select name="base_color_scheme">
                    <flux:select.option value="zinc" :selected="($settings['base_color_scheme'] ?? 'zinc') === 'zinc'">
                        Zinc (Cool & Modern) • Default
                    </flux:select.option>
                    <flux:select.option value="slate" :selected="($settings['base_color_scheme'] ?? 'zinc') === 'slate'">
                        Slate (Professional & Clean)
                    </flux:select.option>
                    <flux:select.option value="gray" :selected="($settings['base_color_scheme'] ?? 'zinc') === 'gray'">
                        Gray (Neutral & Balanced)
                    </flux:select.option>
                    <flux:select.option value="neutral" :selected="($settings['base_color_scheme'] ?? 'zinc') === 'neutral'">
                        Neutral (Warm & Soft)
                    </flux:select.option>
                    <flux:select.option value="stone" :selected="($settings['base_color_scheme'] ?? 'zinc') === 'stone'">
                        Stone (Natural & Earthy)
                    </flux:select.option>
                </flux:select>
                <flux:text size="sm" variant="muted">
                    <flux:icon name="swatch" variant="micro" class="inline" />
                    Controls the subtle gray tones used for backgrounds, borders, and text
                </flux:text>
            </flux:field>

            <flux:separator />

            <flux:field>
                <flux:label>Default Theme</flux:label>
                <flux:select name="default_theme">
                    <flux:select.option value="light" :selected="($settings['default_theme'] ?? 'light') === 'light'">
                        <flux:icon name="sun" variant="micro" class="inline" /> Light Mode
                    </flux:select.option>
                    <flux:select.option value="dark" :selected="($settings['default_theme'] ?? 'light') === 'dark'">
                        <flux:icon name="moon" variant="micro" class="inline" /> Dark Mode
                    </flux:select.option>
                    <flux:select.option value="auto" :selected="($settings['default_theme'] ?? 'light') === 'auto'">
                        <flux:icon name="computer-desktop" variant="micro" class="inline" /> Auto (System Preference)
                    </flux:select.option>
                </flux:select>
                <flux:text size="sm" variant="muted">Theme shown to new users by default</flux:text>
            </flux:field>

            <flux:field variant="inline">
                <flux:checkbox name="allow_theme_switching" :checked="$settings['allow_theme_switching'] ?? true" />
                <div>
                    <flux:label>Allow users to change theme</flux:label>
                    <flux:text size="sm" variant="muted">Let users switch between light and dark mode</flux:text>
                </div>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Theme</flux:heading>
        <flux:text variant="muted" class="mb-6">Application theme preferences</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Default Theme</flux:label>
                <flux:select name="default_theme">
                    <flux:select.option value="light" :selected="($settings['default_theme'] ?? 'light') === 'light'">Light</flux:select.option>
                    <flux:select.option value="dark" :selected="($settings['default_theme'] ?? 'light') === 'dark'">Dark</flux:select.option>
                    <flux:select.option value="auto" :selected="($settings['default_theme'] ?? 'light') === 'auto'">Auto (System)</flux:select.option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Allow Theme Switching</flux:label>
                <flux:switch name="allow_theme_switching" :checked="$settings['allow_theme_switching'] ?? true" />
                <flux:text size="sm" variant="muted">Allow users to change their theme preference</flux:text>
            </flux:field>
        </div>
    </flux:card>
</div>
