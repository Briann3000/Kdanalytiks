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
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Terms and Conditions</h1>
                    </div>

                    <div class="prose prose-indigo prose-lg max-w-none text-gray-600 space-y-6">
                        <p class="text-lg leading-relaxed">
                            <strong>Last updated: {{ date('F d, Y') }}</strong>
                        </p>

                        <section>
                            <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">1. Agreement to
                                Terms</h2>
                            <p>
                                These Terms and Conditions constitute a legally binding agreement made between you, whether
                                personally or on behalf of an entity ("you") and <strong>KDAnalytics</strong> ("Company",
                                "we", "us", or "our"), concerning your access to and use of the KDAnalytics website as well
                                as any other media form, media channel, mobile website or mobile application related, linked
                                or otherwise connected thereto (collectively, the "Site").
                            </p>
                            <p>
                                You agree that by accessing the Site, you have read, understood, and agreed to be bound by
                                all of these Terms and Conditions. IF YOU DO NOT AGREE WITH ALL OF THESE TERMS AND
                                CONDITIONS, THEN YOU ARE EXPRESSLY PROHIBITED FROM USING THE SITE AND YOU MUST DISCONTINUE
                                USE IMMEDIATELY.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">2. Intellectual
                                Property Rights</h2>
                            <p>
                                Unless otherwise indicated, the Site is our proprietary property and all source code,
                                databases, functionality, software, website designs, audio, video, text, photographs, and
                                graphics on the Site (collectively, the "Content") and the trademarks, service marks, and
                                logos contained therein (the "Marks") are owned or controlled by us or licensed to us and
                                are protected by copyright and trademark laws and various other intellectual property rights
                                and unfair competition laws.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">3. User
                                Representations</h2>
                            <p>
                                By using the Site, you represent and warrant that: (1) all registration information you
                                submit will be true, accurate, current, and complete; (2) you will maintain the accuracy of
                                such information and promptly update such registration information as necessary; (3) you
                                have the legal capacity and you agree to comply with these Terms and Conditions; (4) you
                                will not access the Site through automated or non-human means, whether through a bot,
                                script, or otherwise; (5) you will not use the Site for any illegal or unauthorized purpose.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">4. Limitation of
                                Liability</h2>
                            <div class="bg-red-50 border border-red-200 p-4 rounded-lg mb-4 text-red-900 text-sm">
                                <strong>CRITICAL DISCLAIMER OF LIABILITY</strong>
                            </div>
                            <p>
                                IN NO EVENT WILL WE OR OUR DIRECTORS, EMPLOYEES, OR AGENTS BE LIABLE TO YOU OR ANY THIRD
                                PARTY FOR ANY DIRECT, INDIRECT, CONSEQUENTIAL, EXEMPLARY, INCIDENTAL, SPECIAL, OR PUNITIVE
                                DAMAGES, INCLUDING LOST PROFIT, LOST REVENUE, LOSS OF DATA, OR OTHER DAMAGES ARISING FROM
                                YOUR USE OF THE SITE, EVEN IF WE HAVE BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
                            </p>
                            <p class="mt-4 font-bold text-gray-800">
                                Specific Exemptions (Data Breaches & Software Errors):
                            </p>
                            <ul class="list-disc pl-6 space-y-2 mt-2">
                                <li><strong>Data Breaches:</strong> We expressly disclaim any and all liability for data
                                    breaches, unauthorized access, hacking incidents, or cyber-attacks that may compromise
                                    user data, survey responses, or personal information. Users utilize the platform at
                                    their own risk regarding data security.</li>
                                <li><strong>Deployment and Configuration Errors:</strong> We are not liable for any losses,
                                    service interruptions, or data exposure resulting from software deployment errors,
                                    server misconfigurations, failure to secure APIs, or other common technical errors by
                                    our product managers, developers, or infrastructure providers.</li>
                                <li><strong>Platform Downtime:</strong> We do not guarantee continuous, uninterrupted, or
                                    secure access to our services. We are not liable for any damages caused by scheduled or
                                    unscheduled platform downtime.</li>
                            </ul>
                            <p class="mt-4">
                                NOTWITHSTANDING ANYTHING TO THE CONTRARY CONTAINED HEREIN, OUR LIABILITY TO YOU FOR ANY
                                CAUSE WHATSOEVER AND REGARDLESS OF THE FORM OF THE ACTION, WILL AT ALL TIMES BE LIMITED TO
                                THE AMOUNT PAID, IF ANY, BY YOU TO US DURING THE SIX (6) MONTH PERIOD PRIOR TO ANY CAUSE OF
                                ACTION ARISING.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">5.
                                Indemnification</h2>
                            <p>
                                You agree to defend, indemnify, and hold us harmless, including our subsidiaries,
                                affiliates, and all of our respective officers, agents, partners, and employees, from and
                                against any loss, damage, liability, claim, or demand, including reasonable attorneys' fees
                                and expenses, made by any third party due to or arising out of: (1) your Contributions; (2)
                                use of the Site; (3) breach of these Terms and Conditions; (4) any breach of your
                                representations and warranties set forth in these Terms and Conditions.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-xl font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">6. Governing Law
                            </h2>
                            <p>
                                These Terms shall be governed by and defined following the laws of the jurisdiction in which
                                KDAnalytics is legally registered. KDAnalytics and yourself irrevocably consent that the
                                courts of that jurisdiction shall have exclusive jurisdiction to resolve any dispute which
                                may arise in connection with these terms.
                            </p>
                        </section>

                        <div class="mt-12 p-6 bg-gray-50 rounded-xl border border-gray-100">
                            <h2 class="text-lg font-bold text-gray-900 mb-2">Contact Us</h2>
                            <p>In order to resolve a complaint regarding the Site or to receive further information
                                regarding use of the Site, please contact us at:</p>
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