@props([
    'name' => 'cascading',
    'sourceUrl' => '',          // endpoint JSON: { options: [{value,label,children?}] } per level
    'levels' => [],             // [['name'=>'category_id','label'=>'Kategori','placeholder'=>'Pilih kategori'], ...]
    'initial' => [],            // nilai awal per level name
])
<div x-data="eamsCascading(@js($sourceUrl), @js($levels), @js($initial))"
     {{ $attributes->class('eams:grid eams:gap-3 eams:md:grid-cols-3') }} data-eams-component="cascading-select">
    <template x-for="(level, index) in levels" :key="level.name">
        <div class="eams:grid eams:gap-1.5">
            <label :for="'cascading-' + level.name" class="eams:text-[13px] eams:font-semibold eams:text-ink" x-text="level.label"></label>
            <select :id="'cascading-' + level.name" :name="level.name"
                    x-model="values[level.name]"
                    x-on:change="onChange(index)"
                    :disabled="index > 0 && ! values[levels[index - 1].name]"
                    class="eams:block eams:min-h-10 eams:w-full eams:rounded-eams eams:border eams:border-border-strong eams:bg-surface eams:px-3 eams:py-2 eams:text-sm eams:text-ink eams:outline-none eams:transition eams:focus:border-brand eams:focus:ring-2 eams:focus:ring-brand-soft eams:disabled:cursor-not-allowed eams:disabled:bg-surface-sunk eams:disabled:opacity-70">
                <option value="" x-text="level.placeholder || 'Semua'"></option>
                <template x-for="option in optionsFor(index)" :key="option.value">
                    <option :value="option.value" x-text="option.label"></option>
                </template>
            </select>
        </div>
    </template>
</div>
