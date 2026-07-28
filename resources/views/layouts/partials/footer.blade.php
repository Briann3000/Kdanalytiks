<footer class="bg-[#1d2327] border-t border-[#2c3338] text-[#a7aaad] pb-20 md:pb-0 z-0">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-8 lg:px-12">
        
        <!-- Top Section: Multi-Column Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
            
            <!-- Column 1: Brand & Socials -->
            <div class="space-y-4 md:col-span-1">
                <div class="flex items-center space-x-2">
                    <span class="font-bold text-lg text-[#f0f0f1]">KDAnalytiks</span>
                </div>
                <p class="text-xs text-[#a7aaad] leading-relaxed">
                    Advanced survey reporting, significance testing and automated analytical insights for researchers and organizations.
                </p>
                <!-- Social Media Links (X & LinkedIn) -->
                <div class="flex items-center gap-3 pt-2">
                    <a href="https://twitter.com" target="_blank" rel="noopener noreferrer"
                        class="w-9 h-9 rounded-full bg-[#101417] hover:bg-[#2271b1] text-[#a7aaad] hover:text-white flex items-center justify-center transition-colors shadow-sm"
                        title="Follow KDAnalytiks on X (Twitter)">
                        <i class="fa-brands fa-x-twitter text-sm"></i>
                    </a>
                    <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer"
                        class="w-9 h-9 rounded-full bg-[#101417] hover:bg-[#2271b1] text-[#a7aaad] hover:text-white flex items-center justify-center transition-colors shadow-sm"
                        title="Connect with KDAnalytiks on LinkedIn">
                        <i class="fa-brands fa-linkedin-in text-sm"></i>
                    </a>
                </div>
            </div>

            <!-- Column 2: Platform Links -->
            <div>
                <h3 class="text-[#f0f0f1] text-xs font-semibold tracking-wider uppercase mb-4">Platform</h3>
                <ul class="space-y-2 text-xs font-semibold">
                    <li><a href="{{ url('/') }}" class="hover:text-white transition-colors">{{ __('Home') }}</a></li>
                    <li><a href="{{ route('about') }}" class="hover:text-white transition-colors">{{ __('About Us') }}</a></li>
                    <li><a href="{{ route('publications') }}" class="hover:text-white transition-colors">{{ __('Publications') }}</a></li>
                </ul>
            </div>

            <!-- Column 3: Resources & Support -->
            <div>
                <h3 class="text-[#f0f0f1] text-xs font-semibold tracking-wider uppercase mb-4">Resources</h3>
                <ul class="space-y-2 text-xs font-semibold">
                    <li><a href="{{ route('faq') }}" class="hover:text-white transition-colors">{{ __('FAQ') }}</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:text-white transition-colors">{{ __('Contact') }}</a></li>
                    <li><a href="{{ route('privacy') }}" class="hover:text-white transition-colors">{{ __('Privacy Policy') }}</a></li>
                </ul>
            </div>

            <!-- Column 4: Contact & Direct Info -->
            <div>
                <h3 class="text-[#f0f0f1] text-xs font-semibold tracking-wider uppercase mb-4">Get in Touch</h3>
                <ul class="space-y-2 text-xs text-[#a7aaad]">
                    <li>+254 725 788 400</li>
                    <li>
                        <a href="mailto:infokdanalytiks@gmail.com" class="hover:text-white transition-colors">infokdanalytiks@gmail.com</a>
                    </li>
                    <li class="pt-2">
                        Powered by <a href="https://www.kenpro.org" target="_blank" rel="noopener noreferrer" class="font-semibold text-white hover:underline">KENPRO</a>
                    </li>
                </ul>
            </div>

        </div>

        <!-- Bottom Bar: Copyright -->
        <div class="pt-8 border-t border-[#2c3338] flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-[#a7aaad]">
            <div>
                <span class="font-bold text-[#f0f0f1]">KDAnalytiks</span> &copy; {{ date('Y') }}.
                {{ __('All rights reserved.') }}
            </div>
            <div class="text-xs text-[#a7aaad]">
                Empowering data-driven decisions.
            </div>
        </div>

    </div>
</footer>