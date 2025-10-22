# Flux Bar Chart - Code Changes

## Summary
5 minimal changes to `flux.js` + 1 new Blade component = Full bar chart support

---

## Change 1: Add bars template collection

**File**: `vendor/livewire/flux-pro/dist/flux.js`  
**Line**: ~10718

```javascript
// BEFORE
lines: {},
areas: {},
points: {},

// AFTER  
lines: {},
areas: {},
points: {},
bars: {},  // ← Added
```

---

## Change 2: Collect bar templates

**File**: `vendor/livewire/flux-pro/dist/flux.js`  
**Line**: ~10749

```javascript
// AFTER the points collection, ADD:
svgTemplate.content.querySelectorAll('template[name="bar"]').forEach((template) => {
  templates.bars[template.getAttribute("field")] = template;
});
```

---

## Change 3: Add barData() method to series

**File**: `vendor/livewire/flux-pro/dist/flux.js`  
**Line**: ~10244 (after areaPath method)

```javascript
// AFTER areaPath() method, ADD:
barData() {
  let barCount = this.points.length;
  let availableWidth = chart.axes.x.range[1] - chart.axes.x.range[0];
  let barWidth = (availableWidth / barCount) * 0.8;
  let barGap = (availableWidth / barCount) * 0.2;
  let baselineY = chart.axes.y.scale(chart.axes.y.domain[0]);
  return this.points.map((point, index) => ({
    x: point.x - (barWidth / 2),
    y: point.y,
    width: barWidth,
    height: Math.abs(baselineY - point.y),
    datum: point.datum
  }));
},
```

---

## Change 4: Add bar rendering loop

**File**: `vendor/livewire/flux-pro/dist/flux.js`  
**Line**: ~10925 (after points rendering loop)

```javascript
// AFTER the points rendering loop, ADD:
Object.entries(chart.series).forEach(([field, series]) => {
  let barGroupEl = svg.querySelector(`[data-bar-group][data-series="${field}"]`);
  let template = templates.bars[field];
  if (template) {
    svg.querySelector(`[data-bar-group][data-series="${field}"]`)?.remove();
    barGroupEl = document.createElementNS("http://www.w3.org/2000/svg", "g");
    barGroupEl.setAttribute("data-bar-group", "");
    barGroupEl.setAttribute("data-series", field);
    series.barData().forEach((bar, index) => {
      let barEl = hydrateSvgTemplate(template);
      barEl.setAttribute("data-bar", "");
      barEl.setAttribute("data-series", field);
      barEl.setAttribute("data-index", index);
      barEl.setAttribute("x", bar.x);
      barEl.setAttribute("y", bar.y);
      barEl.setAttribute("width", bar.width);
      barEl.setAttribute("height", bar.height);
      barGroupEl.appendChild(barEl);
    });
    svg.appendChild(barGroupEl);
  }
});
```

---

## Change 5: Add bar to field discovery

**File**: `vendor/livewire/flux-pro/dist/flux.js`  
**Line**: ~11258

```javascript
// BEFORE
svg.querySelectorAll('template[name="line"], template[name="area"], template[name="point"]')

// AFTER
svg.querySelectorAll('template[name="line"], template[name="area"], template[name="point"], template[name="bar"]')
//                                                                                          ^^^^^^^^^^^^^^^^ Added
```

---

## Change 6: Create bar Blade component

**File**: `resources/views/flux/chart/bar.blade.php` (NEW FILE)

```blade
@pure

@props([
    'field' => 'value',
])

<template name="bar" field="{{ $field }}">
    <rect {{ $attributes->class('[:where(&)]:text-zinc-800')->merge([
        'fill' => 'currentColor',
        'stroke' => 'none',
    ]) }}></rect>
</template>
```

---

## Total Lines Changed
- **flux.js**: ~50 lines added (out of 12,000+ lines)
- **New files**: 1 (bar.blade.php, 16 lines)
- **Breaking changes**: 0
- **Impact**: Additive only

---

## Verification Commands

```bash
# Check if all changes are present
grep -n "bars: {}," vendor/livewire/flux-pro/dist/flux.js
grep -n 'template\[name="bar"\]' vendor/livewire/flux-pro/dist/flux.js  
grep -n "barData()" vendor/livewire/flux-pro/dist/flux.js
ls -la resources/views/flux/chart/bar.blade.php

# Restore from backup if needed
cp vendor/livewire/flux-pro/dist/flux.js.backup vendor/livewire/flux-pro/dist/flux.js
```

---

## Integration with BaseAnalyticsComponent

To use bar charts in your analytics:

```php
protected function getCharts(): array
{
    return [
        'sales' => [
            'title' => 'Monthly Sales',
            'type' => 'bar',  // ← Set type to 'bar'
            'xAxis' => 'month',
            'fields' => [
                [
                    'key' => 'sales',
                    'label' => 'Sales',
                    'class' => 'text-blue-500 dark:text-blue-400',
                ],
            ],
            'data' => $this->getSalesData(),
        ],
    ];
}
```

The `base-analytics.blade.php` already supports this! Just set `type => 'bar'`.
