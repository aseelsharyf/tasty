{{-- resources/views/components/footer.blade.php --}}
<footer class="mx-auto container bg-tasty-blue-black text-tasty-light-gray font-mono" x-data="{ activeAccordion: null }" aria-label="Tasty">
    <div class="container px-6 pt-20 pb-12">

        {{-- LOGO --}}
        <div class="flex justify-center mb-16 text-tasty-light-gray">
            <x-layout.logo class="w-[200px] md:w-[270px] h-auto" viewBox="0 0 74 36" />
        </div>

        {{-- MOBILE --}}
        <div class="md:hidden flex flex-col w-full max-w-md mx-auto mb-16">

            {{-- Menu --}}
            <div class="border-b border-white/20">
                <button
                    @click="activeAccordion = activeAccordion === 'menu' ? null : 'menu'"
                    :aria-expanded="activeAccordion === 'menu'"
                    aria-controls="foot-menu"
                    class="w-full py-6 flex justify-between items-center text-lg tracking-widest uppercase focus:outline-none"
                >
                    <span>Menu</span>
                    <span x-text="activeAccordion === 'menu' ? '—' : '+'" class="text-2xl font-light" aria-hidden="true"></span>
                </button>

                <div id="foot-menu"
                     x-show="activeAccordion === 'menu'"
                     x-collapse
                     x-cloak
                     role="region"
                     :aria-hidden="activeAccordion === 'menu' ? 'false' : 'true'"
                     class="pb-6 text-white/70 space-y-3 flex flex-col"
                >
                    @foreach($menu as $item)
                        <a href="{{ $item['url'] }}" class="hover:text-white transition">{{ $item['label'] }}</a>
                    @endforeach
                </div>
            </div>

            {{-- Topics --}}
            <div class="border-b border-white/20">
                <button
                    @click="activeAccordion = activeAccordion === 'topics' ? null : 'topics'"
                    :aria-expanded="activeAccordion === 'topics'"
                    aria-controls="foot-topics"
                    class="w-full py-6 flex justify-between items-center text-lg tracking-widest uppercase focus:outline-none"
                >
                    <span>Topics</span>
                    <span x-text="activeAccordion === 'topics' ? '—' : '+'" class="text-2xl font-light" aria-hidden="true"></span>
                </button>

                <div id="foot-topics"
                     x-show="activeAccordion === 'topics'"
                     x-collapse
                     x-cloak
                     role="region"
                     :aria-hidden="activeAccordion === 'topics' ? 'false' : 'true'"
                     class="pb-6 text-white/70 space-y-3 flex flex-col"
                >
                    @foreach($topics as $item)
                        <a href="{{ $item['url'] }}" class="hover:text-white transition">{{ $item['label'] }}</a>
                    @endforeach
                </div>
            </div>

            {{-- Office --}}
            <div class="border-b border-white/20 mb-12">
                <button
                    @click="activeAccordion = activeAccordion === 'office' ? null : 'office'"
                    :aria-expanded="activeAccordion === 'office'"
                    aria-controls="foot-office"
                    class="w-full py-6 flex justify-between items-center text-lg tracking-widest uppercase"
                >
                    <span>Office</span>
                    <span x-text="activeAccordion === 'office' ? '—' : '+'" class="text-2xl font-light" aria-hidden="true"></span>
                </button>

                <div id="foot-office"
                     x-show="activeAccordion === 'office'"
                     x-collapse
                     x-cloak
                     role="region"
                     :aria-hidden="activeAccordion === 'office' ? 'false' : 'true'"
                     class="pb-6 text-white/70 space-y-3 flex flex-col"
                >
                    @foreach($office as $item)
                        <a href="{{ $item['url'] }}" class="hover:text-white transition">{{ $item['label'] }}</a>
                    @endforeach
                </div>
            </div>

            {{-- Social --}}
            <div class="flex flex-col items-center space-y-3 text-lg">
                @foreach($social as $item)
                    <a href="{{ $item['url'] }}" class="hover:text-white transition">{{ $item['label'] }}</a>
                @endforeach
            </div>
        </div>

        {{-- DESKTOP --}}
        <div class="hidden md:grid grid-cols-4 gap-8 lg:gap-12 w-full px-4 lg:px-20 mb-32 text-lg tracking-wide">
            <div class="flex flex-col gap-4">
                @foreach($menu as $item)
                    <a href="{{ $item['url'] }}" class="hover:text-yellow-400 transition">{{ $item['label'] }}</a>
                @endforeach
            </div>

            <div class="flex flex-col gap-4">
                @foreach($topics as $item)
                    <a href="{{ $item['url'] }}" class="hover:text-yellow-400 transition">{{ $item['label'] }}</a>
                @endforeach
            </div>

            <div class="flex flex-col gap-4">
                @foreach($office as $item)
                    <a href="{{ $item['url'] }}" class="hover:text-yellow-400 transition">{{ $item['label'] }}</a>
                @endforeach
            </div>

            <div class="flex flex-col gap-4">
                @foreach($social as $item)
                    <a href="{{ $item['url'] }}" class="hover:text-yellow-400 transition">{{ $item['label'] }}</a>
                @endforeach
            </div>
        </div>

        {{-- COPYRIGHT --}}
        <div class="flex flex-col items-center gap-1 text-xs md:text-sm text-white/60 tracking-wider uppercase">
            <p>&copy; {{ $year }} {{ $company }}</p>
            <p>{{ $location }}</p>
        </div>

    </div>
</footer>
