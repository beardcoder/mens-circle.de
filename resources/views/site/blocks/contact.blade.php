<x-partials.content.wrapper
  :background="$block->input('background')"
  :anchor="$block->input('anchor')"
>
  <div class="mx-auto max-w-screen-md px-6 py-8 lg:py-16">
    <x-partials.content.title data-aos="fade-up">
      {!! $block->input('title') !!}
    </x-partials.content.title>
    @if ($block->input('text'))
      <p
        class="mb-8 text-center sm:text-xl lg:mb-16"
        data-aos="fade-up"
      >
        {!! $block->input('text') !!}
      </p>
    @endif
    <form
      class="mx-auto max-w-md space-y-8"
      method="POST"
      action="{{ route('newsletter.register') }}"
    >
      @csrf
      @honeypot
      <div data-aos="fade-up">
        <label
          class="mb-2 block text-sm font-medium text-stone-900 dark:text-stone-300"
          for="name"
        >Dein Name</label>
        <input
          class="focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light block w-full border border-stone-300 bg-stone-50 p-2.5 text-sm text-stone-900 shadow-sm dark:border-stone-600 dark:bg-stone-700 dark:text-white dark:placeholder-stone-400"
          id="name"
          name="name"
          type="text"
          placeholder="Christian Baumer"
          required
        >
      </div>
      <div data-aos="fade-up">
        <label
          class="mb-2 block text-sm font-medium text-stone-900 dark:text-stone-300"
          for="email"
        >
          Deine Email
        </label>
        <input
          class="focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light block w-full border border-stone-300 bg-stone-50 p-2.5 text-sm text-stone-900 shadow-sm dark:border-stone-600 dark:bg-stone-700 dark:text-white dark:placeholder-stone-400"
          id="email"
          name="email"
          type="email"
          placeholder="christian.baumer@mens-circle.de"
          required
        >
      </div>
      <div data-aos="fade-up">
        <x-button
          class="block w-full"
          type="submit"
          size="md"
        >
          Jetzt Anmelden
        </x-button>
      </div>
    </form>
  </div>
</x-partials.content.wrapper>
