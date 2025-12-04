<section class="w-full bg-tasty-light-gray pb-24 px-4 text-center">
    <div class="mx-auto max-w-4xl">
        <x-ui.heading
            level="h2"
            text="Come hungry, leave inspired. Sign up for tasty updates."
            align="center"
            class="mb-10"
        />

        <form class="max-w-md mx-auto relative flex items-center">
            <div class="w-full flex items-center bg-tasty-pure-white rounded-full p-1.5 border-gray-100 hover:border-gray-300 transition-colors focus-within:border-gray-400 focus-within:ring-1 focus-within:ring-gray-200">
                <input
                    type="email"
                    placeholder="Enter your Email"
                    class="flex-1 bg-transparent px-6 py-2 text-gray-700 placeholder-gray-400 outline-none text-body-md"
                    required
                >

                <button
                    type="submit"
                    class="bg-tasty-yellow hover:bg-tasty-yellow/90 text-tasty-black font-semibold text-body-sm uppercase tracking-wider px-4 md:px-8 py-2 md:py-3 rounded-full transition-all duration-300 flex items-center gap-1 md:gap-2 group whitespace-nowrap flex-shrink-0"
                >
                    Subscribe
                    <i class="fa-solid fa-arrow-right text-xs transform group-hover:translate-x-1 transition-transform"></i>
                </button>
            </div>
        </form>
    </div>
</section>
