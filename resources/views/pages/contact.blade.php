@extends('layouts.app')

@section('content')

<div class="w-full px-5 lg:px-10 pt-32 lg:pt-40 pb-32 bg-tasty-off-white">
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col gap-10">
            <div class="flex flex-col gap-6">
                <x-ui.heading
                    level="h1"
                    text="Contact Us"
                    align="center"
                />
                <p class="text-body-lg text-tasty-blue-black text-center">
                    We'd love to hear from you. Reach out with questions, tips, or just to say hello.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <div class="flex flex-col gap-8">
                    <div class="flex flex-col gap-4">
                        <x-ui.heading
                            level="h3"
                            text="Get in Touch"
                        />
                        <p class="text-body-md text-tasty-blue-black">
                            Whether you have a story tip, feedback on our coverage, or a collaboration idea, we're all ears.
                        </p>
                    </div>

                    <div class="flex flex-col gap-6">
                        <div class="flex flex-col gap-2">
                            <span class="text-body-sm text-tasty-blue-black uppercase tracking-wider">General Inquiries</span>
                            <a href="mailto:hello@tasty.mv" class="text-body-md text-tasty-blue-black hover:opacity-70 transition-opacity">
                                hello@tasty.mv
                            </a>
                        </div>

                        <div class="flex flex-col gap-2">
                            <span class="text-body-sm text-tasty-blue-black uppercase tracking-wider">Editorial</span>
                            <a href="mailto:editor@tasty.mv" class="text-body-md text-tasty-blue-black hover:opacity-70 transition-opacity">
                                editor@tasty.mv
                            </a>
                        </div>

                        <div class="flex flex-col gap-2">
                            <span class="text-body-sm text-tasty-blue-black uppercase tracking-wider">Advertising</span>
                            <a href="mailto:ads@tasty.mv" class="text-body-md text-tasty-blue-black hover:opacity-70 transition-opacity">
                                ads@tasty.mv
                            </a>
                        </div>

                        <div class="flex flex-col gap-2">
                            <span class="text-body-sm text-tasty-blue-black uppercase tracking-wider">Location</span>
                            <p class="text-body-md text-tasty-blue-black">
                                Malé, Maldives
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4">
                        <span class="text-body-sm text-tasty-blue-black uppercase tracking-wider">Follow Us</span>
                        <div class="flex gap-4">
                            <a href="#" class="text-tasty-blue-black hover:opacity-70 transition-opacity">
                                <i class="fab fa-instagram text-xl"></i>
                            </a>
                            <a href="#" class="text-tasty-blue-black hover:opacity-70 transition-opacity">
                                <i class="fab fa-tiktok text-xl"></i>
                            </a>
                            <a href="#" class="text-tasty-blue-black hover:opacity-70 transition-opacity">
                                <i class="fab fa-youtube text-xl"></i>
                            </a>
                            <a href="#" class="text-tasty-blue-black hover:opacity-70 transition-opacity">
                                <i class="fab fa-x-twitter text-xl"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 lg:p-8">
                    <form class="flex flex-col gap-6">
                        <div class="flex flex-col gap-2">
                            <label for="name" class="text-body-sm text-tasty-blue-black uppercase tracking-wider">Name</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg text-body-md focus:outline-none focus:border-tasty-blue-black transition-colors"
                                placeholder="Your name"
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="email" class="text-body-sm text-tasty-blue-black uppercase tracking-wider">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg text-body-md focus:outline-none focus:border-tasty-blue-black transition-colors"
                                placeholder="your@email.com"
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="subject" class="text-body-sm text-tasty-blue-black uppercase tracking-wider">Subject</label>
                            <select
                                id="subject"
                                name="subject"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg text-body-md focus:outline-none focus:border-tasty-blue-black transition-colors bg-white"
                            >
                                <option value="">Select a topic</option>
                                <option value="general">General Inquiry</option>
                                <option value="story">Story Tip</option>
                                <option value="feedback">Feedback</option>
                                <option value="collaboration">Collaboration</option>
                                <option value="advertising">Advertising</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="message" class="text-body-sm text-tasty-blue-black uppercase tracking-wider">Message</label>
                            <textarea
                                id="message"
                                name="message"
                                rows="5"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg text-body-md focus:outline-none focus:border-tasty-blue-black transition-colors resize-none"
                                placeholder="Your message..."
                            ></textarea>
                        </div>

                        <x-ui.button
                            url="#"
                            text="Send Message"
                            icon="arrow-right"
                            class="self-start"
                        />
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
