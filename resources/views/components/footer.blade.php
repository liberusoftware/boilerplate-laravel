<footer class="bg-opacity-70">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 bg-opacity-80">
        <div class="flex justify-between items-center bg-opacity-80">
            <div>
                <a href="/" class="text-lg font-semibold">{{ config('app.name') }}</a>
            </div>
            <nav>
                <ul class="flex space-x-4">
	            <li><a href="/about" class="hover:text-gray-300">About Us</a></li>
                    <li><a href="/privacy" class="hover:text-gray-300">Privacy</a></li>
                    <li><a href="/terms-and-conditions" class="hover:text-gray-300">Terms &amp; Conditions</a></li>
                    <li><a href="https://wa.me/447706007407" class="hover:text-gray-300">Contact on WhatsApp</a></li>
                </ul>
            </nav>
        </div>
        <div class="text-center">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</footer>
