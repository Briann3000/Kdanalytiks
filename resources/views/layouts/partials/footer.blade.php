<footer class="bg-gray-800 border-t border-gray-700 pb-20 md:pb-0 z-0">
    <div class="max-w-full mx-auto py-8 px-4 sm:px-8 lg:px-12 text-center text-sm text-gray-300">
        <div class="mb-2">
            <span class="font-bold text-white">KMSurveyTool</span> &copy; {{ date('Y') }}.
            {{ __('All rights reserved.') }}
        </div>
        <div class="flex flex-col md:flex-row items-center justify-center md:space-x-2 space-y-2 md:space-y-0">
            <span>+254 725 788 400</span>
            <span class="hidden md:inline text-gray-500">|</span>
            <span>Powered by <span class="font-semibold text-white">PRC™ Consulting</span></span>
            <span class="hidden md:inline text-gray-500">|</span>
            <a href="mailto:info@kmsurveytool.com" class="hover:text-white transition-colors">info@kmsurveytool.com</a>
            <span class="hidden md:inline text-gray-500">|</span>
            <a href="{{ route('privacy') }}"
                class="hover:text-white transition-colors font-medium">{{ __('Privacy Policy') }}</a>
        </div>
    </div>
</footer>