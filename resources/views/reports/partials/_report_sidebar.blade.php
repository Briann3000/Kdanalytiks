<!-- Report Sub-Sidebar (Isolated Pane) -->
<aside 
    :class="sidebarOpen ? 'w-60' : 'w-12'"
    class="bg-white border-r border-gray-100 flex-shrink-0 flex flex-col z-30 shadow-sm transition-all duration-300 overflow-hidden">

    <div class="p-4 flex-grow overflow-y-auto custom-scrollbar">
        <div x-show="sidebarOpen" x-transition class="mb-6">
            <h3 class="text-[9px] font-black text-gray-400 tracking-[0.2em] mb-4">Reports</h3>
        </div>

        <!-- Vertical Label for Minimized State -->
        <div x-show="!sidebarOpen" class="flex flex-col items-center py-4 space-y-8 opacity-40">
            <span class="[writing-mode:vertical-lr] rotate-180 text-[10px] font-black text-gray-500 tracking-[0.3em] uppercase">Sections</span>
        </div>
        
        <nav class="space-y-1">
            @php
                $isReportPage = request()->routeIs('surveys.report');
                $reportUrl = route('surveys.report', $survey);
                $activeClass = 'bg-zinc-100 text-[#135e96]';
                $inactiveClass = 'text-gray-500 hover:bg-gray-50';
                $commonClasses = 'w-full flex items-center px-3 py-2.5 text-[13px] font-black rounded-xl transition-all group';
            @endphp

            <!-- Overview Link -->
            <a href="{{ $reportUrl }}?tab=overview" 
                @if($isReportPage) @click.prevent="$store.workspace.setTab('overview')" @endif
                :class="($store.workspace.activeTab === 'overview' && {{ $isReportPage ? 'true' : 'false' }}) ? '{{ $activeClass }}' : '{{ $inactiveClass }}'"
                class="{{ $commonClasses }}">
                <i class="fa-solid fa-gauge-high w-5 text-center mr-3" :class="($store.workspace.activeTab === 'overview' && {{ $isReportPage ? 'true' : 'false' }}) ? 'text-[#2271b1]' : 'text-gray-300 group-hover:text-zinc-500'"></i>
                <span x-show="sidebarOpen">Overview</span>
            </a>

            <!-- Quantitative Link -->
            <div class="space-y-1">
                <a href="{{ $reportUrl }}?tab=quantitative" 
                    @if($isReportPage) @click.prevent="$store.workspace.setTab('quantitative')" @endif
                    :class="($store.workspace.activeTab === 'quantitative' && {{ $isReportPage ? 'true' : 'false' }}) ? '{{ $activeClass }}' : '{{ $inactiveClass }}'"
                    class="{{ $commonClasses }}">
                    <i class="fa-solid fa-chart-line w-5 text-center mr-3" :class="($store.workspace.activeTab === 'quantitative' && {{ $isReportPage ? 'true' : 'false' }}) ? 'text-[#2271b1]' : 'text-gray-300 group-hover:text-zinc-500'"></i>
                    <span x-show="sidebarOpen">Quantitative</span>
                </a>
                @if(isset($analysis) && $isReportPage)
                <div x-show="$store.workspace.activeTab === 'quantitative' && sidebarOpen" x-transition class="ml-8 space-y-1 pt-1 pb-2 border-l border-gray-100 pl-4">
                    @foreach($analysis as $item)
                    @if($item['isChartable'])
                    <button @click="$store.workspace.scrollTo('q-{{ $item['id'] }}')" class="block w-full text-left text-[10px] font-bold text-gray-400 hover:text-[#2271b1] transition-colors truncate">
                        #{{ $loop->iteration }} {{ str($item['label'])->limit(20) }}
                    </button>
                    @endif
                    @endforeach
                </div>
                @endif
            </div>

            <!-- AI Insights Link -->
            <div class="space-y-1">
                <a href="{{ $reportUrl }}?tab=qualitative" 
                    @if($isReportPage) @click.prevent="$store.workspace.setTab('qualitative')" @endif
                    :class="($store.workspace.activeTab === 'qualitative' && {{ $isReportPage ? 'true' : 'false' }}) ? '{{ $activeClass }}' : '{{ $inactiveClass }}'"
                    class="{{ $commonClasses }}">
                    <i class="fa-solid fa-brain w-5 text-center mr-3" :class="($store.workspace.activeTab === 'qualitative' && {{ $isReportPage ? 'true' : 'false' }}) ? 'text-[#2271b1]' : 'text-gray-300 group-hover:text-zinc-500'"></i>
                    <span x-show="sidebarOpen">AI Insights</span>
                </a>
                @if(isset($analysis) && $isReportPage)
                <div x-show="$store.workspace.activeTab === 'qualitative' && sidebarOpen" x-transition class="ml-8 space-y-1 pt-1 pb-2 border-l border-gray-100 pl-4">
                    @foreach($analysis as $item)
                    @if(!$item['isChartable'])
                    <button @click="$store.workspace.scrollTo('ql-{{ $item['id'] }}')" class="block w-full text-left text-[10px] font-bold text-gray-400 hover:text-[#2271b1] transition-colors truncate">
                        Q{{ $loop->iteration }} {{ str($item['label'])->limit(20) }}
                    </button>
                    @endif
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Data Table Link -->
            <a href="{{ $reportUrl }}?tab=tables" 
                @if($isReportPage) @click.prevent="$store.workspace.setTab('tables')" @endif
                :class="($store.workspace.activeTab === 'tables' && {{ $isReportPage ? 'true' : 'false' }}) ? '{{ $activeClass }}' : '{{ $inactiveClass }}'"
                class="{{ $commonClasses }}">
                <i class="fa-solid fa-table w-5 text-center mr-3" :class="($store.workspace.activeTab === 'tables' && {{ $isReportPage ? 'true' : 'false' }}) ? 'text-[#2271b1]' : 'text-gray-300 group-hover:text-zinc-500'"></i>
                <span x-show="sidebarOpen">Data Table</span>
            </a>

            <!-- Submissions (Responses) Link -->
            <a href="{{ route('surveys.responses', $survey) }}" 
                class="{{ $commonClasses }} {{ request()->routeIs('surveys.responses') ? $activeClass : $inactiveClass }}">
                <i class="fa-solid fa-users w-5 text-center mr-3 {{ request()->routeIs('surveys.responses') ? 'text-[#2271b1]' : 'text-gray-300 group-hover:text-zinc-500' }}"></i>
                <span x-show="sidebarOpen">Submissions</span>
            </a>

            <!-- Gallery Link -->
            <a href="{{ $reportUrl }}?tab=gallery" 
                @if($isReportPage) @click.prevent="$store.workspace.setTab('gallery')" @endif
                :class="($store.workspace.activeTab === 'gallery' && {{ $isReportPage ? 'true' : 'false' }}) ? '{{ $activeClass }}' : '{{ $inactiveClass }}'"
                class="{{ $commonClasses }}">
                <i class="fa-solid fa-images w-5 text-center mr-3" :class="($store.workspace.activeTab === 'gallery' && {{ $isReportPage ? 'true' : 'false' }}) ? 'text-[#2271b1]' : 'text-gray-300 group-hover:text-zinc-500'"></i>
                <span x-show="sidebarOpen">Gallery</span>
            </a>
        </nav>
    </div>
    
    <div x-show="sidebarOpen" class="p-4 border-t border-gray-50 text-[9px] text-gray-400 font-bold uppercase tracking-widest">
        ID: #{{ $survey->id }}
    </div>
</aside>
