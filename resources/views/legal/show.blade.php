<x-public-layout :title="$title" maxWidth="3xl">
    <main class="flex-1 py-12 px-6">
        <div class="max-w-3xl mx-auto">
            <div class="mb-8">
                <p class="text-xs font-semibold uppercase tracking-widest text-ink-400 mb-2">Legal</p>
                <h1 class="text-3xl font-bold tracking-tight">{{ $title }}</h1>
                <p class="mt-2 text-sm text-ink-400">{{ $subtitle }}</p>
            </div>

            <div class="prose prose-invert prose-sm max-w-none
                        prose-headings:font-semibold prose-headings:tracking-tight
                        prose-h2:text-lg prose-h2:mt-10 prose-h2:mb-3 prose-h2:text-ink-100
                        prose-h3:text-base prose-h3:mt-6 prose-h3:mb-2 prose-h3:text-ink-200
                        prose-p:text-ink-300 prose-p:leading-relaxed
                        prose-li:text-ink-300 prose-li:leading-relaxed
                        prose-a:text-blaze-400 prose-a:no-underline hover:prose-a:underline
                        prose-strong:text-ink-100
                        prose-hr:border-ink-700">
                {!! $html !!}
            </div>
        </div>
    </main>
</x-public-layout>
