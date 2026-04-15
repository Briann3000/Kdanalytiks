@extends('layouts.app')

@section('content')
    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-2xl overflow-hidden border border-gray-100">
                <div class="px-8 py-10">
                    <div class="flex items-center space-x-3 mb-8">
                        <div class="p-3 bg-indigo-50 rounded-xl">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04a11.357 11.357 0 00-1.173 4.593c0 3.823 2.064 7.122 5.107 8.597L12 22l4.684-2.831c3.043-1.475 5.107-4.774 5.107-8.597a11.357 11.357 0 00-1.173-4.593z">
                                </path>
                            </svg>
                        </div>
                        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Privacy Policy</h1>
                    </div>

                    <div class="prose prose-indigo prose-lg max-w-none text-gray-600 space-y-6">
                        <p class="text-lg leading-relaxed">
                            Last updated: {{ date('F d, Y') }}
                        </p>

                        <section>
                            <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">1. Introduction
                            </h2>
                            <p>
                                Welcome to <strong>KMSurveyTool</strong>. We respect your privacy and are committed to
                                protecting your personal data. This privacy policy informs you how we look after your
                                personal data when you visit our website or use our mobile application and tells you about
                                your privacy rights and how the law protects you.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">2. Data We
                                Collect</h2>
                            <p>We may collect, use, store and transfer different kinds of personal data about you which we
                                have grouped together as follows:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li><strong>Identity Data:</strong> Includes first name, last name, username or similar
                                    identifier.</li>
                                <li><strong>Contact Data:</strong> Includes email address and telephone numbers.</li>
                                <li><strong>Survey Data:</strong> Includes survey responses, question designs, and metadata
                                    associated with survey completion.</li>
                                <li><strong>Technical Data:</strong> Includes internet protocol (IP) address, your login
                                    data, browser type and version, time zone setting and location, browser plug-in types
                                    and versions, operating system and platform, and other technology on the devices you use
                                    to access this website.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">3. How We Use
                                Your Data</h2>
                            <p>We will only use your personal data when the law allows us to. Most commonly, we will use
                                your personal data in the following circumstances:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>To provide and maintain our Service, including to monitor the usage of our Service.</li>
                                <li>To manage Your Account: to manage Your registration as a user of the Service.</li>
                                <li>For the performance of a contract: the development, compliance and undertaking of the
                                    purchase contract for the products, items or services You have purchased or of any other
                                    contract with Us through the Service.</li>
                                <li>To contact You: To contact You by email, telephone calls, SMS, or other equivalent forms
                                    of electronic communication.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">4. Data Security
                            </h2>
                            <p>
                                We have put in place appropriate security measures to prevent your personal data from being
                                accidentally lost, used or accessed in an unauthorized way, altered or disclosed. In
                                addition, we limit access to your personal data to those employees, agents, contractors and
                                other third parties who have a business need to know.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">5. Your Legal
                                Rights</h2>
                            <p>
                                Under certain circumstances, you have rights under data protection laws in relation to your
                                personal data, including the right to request access, correction, erasure, restriction,
                                transfer, to object to processing, and to withdraw consent.
                            </p>
                        </section>

                        <div class="mt-12 p-6 bg-gray-50 rounded-xl border border-gray-100">
                            <h2 class="text-lg font-bold text-gray-900 mb-2">Contact Us</h2>
                            <p>If you have any questions about this privacy policy or our privacy practices, please contact
                                us at:</p>
                            <p class="mt-2 text-indigo-600 font-medium">support@kmsurveytool.com</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 text-center">
                <a href="{{ route('home') }}"
                    class="text-indigo-600 hover:text-indigo-500 font-medium transition duration-150">
                    &larr; Back to Home
                </a>
            </div>
        </div>
    </div>
@endsection