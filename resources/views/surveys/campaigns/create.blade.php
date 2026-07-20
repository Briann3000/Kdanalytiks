@extends('surveys.hub')

@section('survey-content')
    <div class="max-w-3xl">
        <!-- Back Link -->
        <div class="mb-6">
            <a href="{{ route('surveys.campaigns.index', $survey) }}"
                class="text-xs font-bold text-gray-600 hover:text-[#2271b1] transition-colors">
                <i class="fa-solid fa-arrow-left mr-1"></i> {{ __('Back to Campaigns') }}
            </a>
        </div>

        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-8 border-b border-gray-50">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Create Invite Campaign') }}</h3>
                <p class="text-xs text-gray-400 font-medium mt-1">
                    {{ __('Set up your invitation campaign, select your recipients, and configure reminder emails.') }}</p>
            </div>

            <form action="{{ route('surveys.campaigns.store', $survey) }}" method="POST" enctype="multipart/form-data"
                class="p-8 space-y-6">
                @csrf

                <!-- Campaign Name -->
                <div class="space-y-2">
                    <label for="name"
                        class="block text-xs font-bold text-gray-700 uppercase tracking-wider">{{ __('Campaign Name') }}
                        <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" required
                        value="{{ old('name', __('Wave :count Outreach', ['count' => count($survey->campaigns) + 1])) }}"
                        class="w-full px-4 py-3 bg-gray-50/50 border border-gray-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-[#2271b1] focus:bg-white transition-all"
                        placeholder="e.g. Wave 1 Customer Outreach">
                    @error('name')
                        <p class="text-rose-500 text-[10px] font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Recipient Source (CSV or Manual Text Area) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- CSV File Upload -->
                    <div class="p-6 bg-gray-50/50 border border-gray-100 rounded-2xl space-y-3">
                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            {{ __('Upload CSV List') }}</h4>
                        <p class="text-[10px] text-gray-400 font-medium leading-relaxed">
                            {{ __('Upload a CSV file containing your recipients. We expect a column with header "email" (required) and optionally "name".') }}
                        </p>
                        <div class="pt-2">
                            <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt"
                                class="block w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:tracking-wider file:bg-zinc-100 file:text-[#2271b1] hover:file:bg-zinc-200 transition-all cursor-pointer">
                        </div>
                        @error('csv_file')
                            <p class="text-rose-500 text-[10px] font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Manual Input Text Area -->
                    <div class="p-6 bg-gray-50/50 border border-gray-100 rounded-2xl space-y-3">
                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            {{ __('Manually Enter Emails') }}</h4>
                        <p class="text-[10px] text-gray-400 font-medium leading-relaxed">
                            {{ __('Alternatively, type or paste email addresses separated by commas, semicolons, or newlines.') }}
                        </p>
                        <textarea name="emails" id="emails" rows="3"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-[#2271b1] focus:bg-white transition-all"
                            placeholder="email1@example.com, email2@example.com">{{ old('emails') }}</textarea>
                        @error('emails')
                            <p class="text-rose-500 text-[10px] font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Scheduling & Reminders Options -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-50">
                    <!-- Scheduling Options -->
                    <div class="space-y-4">
                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('Scheduling') }}
                        </h4>
                        <div class="space-y-2">
                            <label for="scheduled_at"
                                class="block text-[10px] font-bold text-gray-600 uppercase tracking-wider">{{ __('Send Date & Time') }}</label>
                            <input type="datetime-local" name="scheduled_at" id="scheduled_at"
                                value="{{ old('scheduled_at') }}"
                                class="w-full px-4 py-3 bg-gray-50/50 border border-gray-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-[#2271b1] focus:bg-white transition-all">
                            <p class="text-[9px] text-gray-400 font-medium">
                                {{ __('Leave empty to send immediately upon saving.') }}</p>
                            @error('scheduled_at')
                                <p class="text-rose-500 text-[10px] font-bold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Auto-Reminder Options -->
                    <div class="space-y-4" x-data="{ autoReminders: {{ old('auto_reminders') ? 'true' : 'false' }} }">
                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            {{ __('Automated Reminders') }}</h4>

                        <div class="flex items-center space-x-3 pt-2">
                            <input type="checkbox" name="auto_reminders" id="auto_reminders" value="1"
                                x-model="autoReminders"
                                class="h-4 w-4 text-[#2271b1] border-gray-300 rounded focus:ring-[#2271b1] cursor-pointer">
                            <label for="auto_reminders"
                                class="text-xs font-bold text-gray-700 uppercase tracking-wider cursor-pointer">
                                {{ __('Enable Auto Reminders') }}
                            </label>
                        </div>

                        <div class="space-y-2 transition-all duration-300" x-show="autoReminders" x-cloak>
                            <label for="reminder_interval_days"
                                class="block text-[10px] font-bold text-gray-600 uppercase tracking-wider">{{ __('Reminder Interval (Days)') }}</label>
                            <select name="reminder_interval_days" id="reminder_interval_days"
                                class="w-full px-4 py-3 bg-gray-50/50 border border-gray-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-[#2271b1] focus:bg-white transition-all">
                                <option value="1" {{ old('reminder_interval_days') == 1 ? 'selected' : '' }}>
                                    {{ __('Every 1 day') }}</option>
                                <option value="2" {{ old('reminder_interval_days') == 2 ? 'selected' : '' }}>
                                    {{ __('Every 2 days') }}</option>
                                <option value="3" {{ old('reminder_interval_days') == 3 || !old('reminder_interval_days') ? 'selected' : '' }}>{{ __('Every 3 days') }}</option>
                                <option value="5" {{ old('reminder_interval_days') == 5 ? 'selected' : '' }}>
                                    {{ __('Every 5 days') }}</option>
                                <option value="7" {{ old('reminder_interval_days') == 7 ? 'selected' : '' }}>
                                    {{ __('Every 7 days') }}</option>
                                <option value="10" {{ old('reminder_interval_days') == 10 ? 'selected' : '' }}>
                                    {{ __('Every 10 days') }}</option>
                            </select>
                            <p class="text-[9px] text-gray-400 font-medium">
                                {{ __('Remind non-respondents repeatedly at this interval.') }}</p>
                            @error('reminder_interval_days')
                                <p class="text-rose-500 text-[10px] font-bold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Submit buttons -->
                <div class="pt-6 border-t border-gray-50 flex items-center justify-end space-x-3">
                    <a href="{{ route('surveys.campaigns.index', $survey) }}"
                        class="px-6 py-3 bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit"
                        class="px-8 py-4 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-zinc-200/50 transition-all transform hover:-translate-y-1">
                        <i class="fa-solid fa-paper-plane mr-2"></i> {{ __('Save & Start Campaign') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection