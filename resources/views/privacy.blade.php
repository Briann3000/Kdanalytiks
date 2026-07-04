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
                            <strong>Last updated: {{ date('F d, Y') }}</strong>
                        </p>

                        <section>
                            <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">1. Introduction
                            </h2>
                            <p>
                                Welcome to <strong>KDAnalytiks</strong> ("Company", "we", "our", "us"). We respect your
                                privacy and are committed to
                                protecting your personal data. This comprehensive Privacy Policy explains how we collect,
                                use, disclose and safeguard your
                                information when you visit our website, use our survey platform or interact with our
                                services. Please read this privacy policy carefully. If you do not agree with the terms of
                                this privacy policy, please do not access the site.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">2. Data We
                                Collect</h2>
                            <p>We may collect, use, store and transfer different kinds of personal data about you which we
                                have grouped together as follows:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li><strong>Identity Data:</strong> Includes first name, last name, username or similar
                                    identifier, marital status, title, date of birth and gender.</li>
                                <li><strong>Contact Data:</strong> Includes billing address, delivery address, email address
                                    and telephone numbers.</li>
                                <li><strong>Survey Data:</strong> Includes survey responses, question designs, uploaded
                                    media, logic configurations, and metadata
                                    associated with survey completion and respondent behaviors.</li>
                                <li><strong>Technical Data:</strong> Includes internet protocol (IP) address, your login
                                    data, browser type and version, time zone setting and location, browser plug-in types
                                    and versions, operating system and platform, and other technology on the devices you use
                                    to access this website.</li>
                                <li><strong>Usage Data:</strong> Includes information about how you use our website,
                                    products and services.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">3. How We Use
                                Your Data</h2>
                            <p>We will only use your personal data when the law allows us to. Most commonly, we will use
                                your personal data in the following circumstances:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li><strong>Service Delivery:</strong> To provide and maintain our Service, including to
                                    monitor the usage of our Service, manage user accounts, and process transactions.</li>
                                <li><strong>Survey Facilitation:</strong> To process, route, and store survey responses on
                                    behalf of survey creators. Note that survey creators are the data controllers for the
                                    survey responses they collect.</li>
                                <li><strong>Communication:</strong> To contact You by email, telephone calls, SMS, or other
                                    equivalent forms
                                    of electronic communication regarding updates, security alerts, and administrative
                                    messages.</li>
                                <li><strong>Analytics & Improvement:</strong> To use data analytics to improve our website,
                                    products/services, marketing, customer relationships and experiences.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">4. Data Security
                                and Limitation of Liability
                            </h2>
                            <div class="bg-amber-50 border border-amber-200 p-4 rounded-lg mb-4 text-amber-900 text-sm">
                                <strong>IMPORTANT NOTICE REGARDING DATA BREACHES AND SOFTWARE ERRORS</strong>
                            </div>
                            <p>
                                We have implemented commercially reasonable security measures designed to secure your
                                personal information from accidental loss and from unauthorized access, use, alteration and
                                disclosure. However, the transmission of information via the internet is not completely
                                secure.
                            </p>
                            <p class="mt-4 font-bold text-gray-800">
                                Limitation of Liability for Data Breaches:
                            </p>
                            <p>
                                While we strive to protect your personal data, KDAnalytiks cannot guarantee the absolute
                                security of your data transmitted to our site or stored on our servers. You acknowledge and
                                agree that KDAnalytiks, its affiliates, directors, employees and agents shall not be held
                                liable for any data breaches, unauthorized access, hacking, data loss or other security
                                intrusions. By using our platform, you accept the inherent risks associated with online data
                                storage and transmission.
                            </p>
                            <p class="mt-4 font-bold text-gray-800">
                                Software and Deployment Errors:
                            </p>
                            <p>
                                Software deployment processes, third-party integrations, and platform updates may
                                occasionally result in unforeseen bugs, configuration errors or temporary vulnerabilities.
                                KDAnalytiks explicitly disclaims any liability for damages, loss of business, or data
                                exposure resulting from deployment errors, server misconfigurations, zero-day
                                vulnerabilities or failures by product managers and engineers to foresee specific security
                                vectors during routine updates or scaling operations.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">5. Data Retention
                            </h2>
                            <p>We will only retain your personal data for as long as necessary to fulfil the purposes we
                                collected it for, including for the purposes of satisfying any legal, accounting, or
                                reporting requirements. When we have no ongoing legitimate business need to process your
                                personal information, we will either delete or anonymize it.</p>
                        </section>

                        <section>
                            <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">6. Your Legal
                                Rights</h2>
                            <p>
                                Depending on your location (e.g., under GDPR or CCPA), you may have rights under data
                                protection laws in relation to your
                                personal data, including the right to:
                            </p>
                            <ul class="list-disc pl-6 space-y-2 mt-2">
                                <li>Request access to your personal data.</li>
                                <li>Request correction of your personal data.</li>
                                <li>Request erasure of your personal data.</li>
                                <li>Object to processing of your personal data.</li>
                                <li>Request restriction of processing your personal data.</li>
                                <li>Request transfer of your personal data.</li>
                                <li>Right to withdraw consent.</li>
                            </ul>
                        </section>

                        <div class="mt-12 p-6 bg-gray-50 rounded-xl border border-gray-100">
                            <h2 class="text-lg font-bold text-gray-900 mb-2">Contact Us</h2>
                            <p>If you have any questions about this privacy policy or our privacy practices, please contact
                                us at:</p>
                            <p class="mt-2 text-indigo-600 font-medium">info@kmsurveytool.com</p>
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