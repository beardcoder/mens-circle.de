@php
  $hasImage = $block->hasImage('text_image');
@endphp

<section class="{{ $block->input('background') ? 'bg-white dark:bg-stone-800' : 'bg-stone-100 dark:bg-stone-900' }}">
  <div
    class="{{ $hasImage ? 'lg:grid lg:grid-cols-2' : '' }} mx-auto max-w-screen-xl items-center gap-16 px-4 py-8 lg:px-6 lg:py-16"
  >
    <div class="{{ $hasImage ? '' : 'mx-auto' }} max-w-4xl font-light text-stone-500 dark:text-stone-400 sm:text-lg">
      <h2 class="mb-4 text-4xl font-extrabold tracking-tight text-stone-900 dark:text-white">
        {!! $block->input('title') !!}
      </h2>
      <div class="format lg:format-lg dark:format-invert max-w-none">
        {!! $block->input('text') !!}
      </div>
    </div>
    @if ($hasImage)
      <div class="mt-8">
        <img
          class="w-full"
          src="{{ $block->image('text_image') }}"
          alt="{{ $block->imageAltText('text_image') }}"
        />
      </div>
    @endif
  </div>
</section>
