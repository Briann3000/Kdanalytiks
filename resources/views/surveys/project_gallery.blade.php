@extends('surveys.project_hub')

@section('project-content')
<div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden" 
     x-data="{ 
        search: '', 
        showLightbox: false, 
        activeMedia: null,
        media: {{ json_encode($mediaFiles) }},
        get filteredMedia() {
            if (!this.search) return this.media;
            return this.media.filter(m => m.path.toLowerCase().includes(this.search.toLowerCase()));
        },
        openLightbox(file) {
            this.activeMedia = file;
            this.showLightbox = true;
        }
     }">
    <div class="px-8 py-6 border-b border-gray-50 bg-slate-50/50 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h5 class="text-sm font-black text-gray-900 uppercase tracking-widest leading-none">Media Gallery</h5>
            <p class="text-[9px] text-gray-500 font-bold uppercase mt-1 tracking-wider">All media assets submitted by survey respondents</p>
        </div>
        <div class="flex items-center gap-3 w-full md:w-auto">
            <div class="relative flex-grow">
                <input type="text" x-model="search" placeholder="Search by filename..." 
                       class="w-full md:w-64 pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-[10px] font-bold focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[10px]"></i>
            </div>
            <span class="px-3 py-2 bg-white border border-gray-200 rounded-xl text-[10px] font-black text-indigo-600 shadow-sm whitespace-nowrap">
                <span x-text="filteredMedia.length"></span> / {{ count($mediaFiles) }} Assets
            </span>
        </div>
    </div>

    <div class="p-8">
        <template x-if="filteredMedia.length > 0">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                <template x-for="(file, index) in filteredMedia" :key="index">
                    <div class="group relative bg-gray-50 rounded-2xl overflow-hidden border border-gray-100 transition-all hover:shadow-2xl hover:border-indigo-200 hover:-translate-y-1 cursor-zoom-in"
                         @click="openLightbox(file)">
                        <!-- Media Preview -->
                        <div class="aspect-square flex items-center justify-center bg-gray-100/50 relative">
                            <template x-if="file.type === 'image'">
                                <img :src="'/storage/' + file.path" class="w-full h-full object-cover" alt="Response Media">
                            </template>
                            <template x-if="file.type === 'video'">
                                <div class="flex flex-col items-center">
                                    <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 mb-2">
                                        <i class="fa-solid fa-play text-sm"></i>
                                    </div>
                                    <span class="text-[8px] font-black uppercase text-gray-500">Video Asset</span>
                                </div>
                            </template>
                            <template x-if="file.type === 'audio'">
                                <div class="flex flex-col items-center">
                                    <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 mb-2">
                                        <i class="fa-solid fa-microphone text-sm"></i>
                                    </div>
                                    <span class="text-[8px] font-black uppercase text-gray-500">Audio Voice</span>
                                </div>
                            </template>
                            <template x-if="file.type === 'file'">
                                <div class="flex flex-col items-center">
                                    <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 mb-2">
                                        <i class="fa-solid fa-file-invoice text-sm"></i>
                                    </div>
                                    <span class="text-[8px] font-black uppercase text-gray-500">Document</span>
                                </div>
                            </template>

                            <!-- Badge -->
                            <div class="absolute top-2 right-2 px-1.5 py-0.5 bg-white/90 backdrop-blur-md border border-gray-100 rounded md text-[8px] font-black text-gray-900 uppercase tracking-tighter shadow-sm" x-text="file.type"></div>
                        </div>
                        
                        <!-- Details -->
                        <div class="p-3 bg-white border-t border-gray-50">
                            <div class="text-[9px] font-bold text-gray-900 truncate" x-text="file.path.split('/').pop()"></div>
                            <div class="mt-1 flex items-center text-[8px] text-gray-400 font-bold uppercase tracking-widest">
                                <i class="fa-solid fa-calendar mr-1 opacity-50"></i> <span x-text="file.date"></span>
                            </div>
                        </div>

                        <!-- Hover Actions -->
                        <div class="absolute inset-x-0 bottom-0 p-2 bg-indigo-900/10 backdrop-blur-sm flex justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                             <a :href="'/storage/' + file.path" download @click.stop 
                                class="w-8 h-8 bg-white border border-gray-100 rounded-lg flex items-center justify-center text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                <i class="fa-solid fa-download text-[10px]"></i>
                            </a>
                            <button type="button" 
                               class="w-8 h-8 bg-white border border-gray-100 rounded-lg flex items-center justify-center text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                <i class="fa-solid fa-expand text-[10px]"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <template x-if="filteredMedia.length === 0">
            <div class="text-center py-32 bg-gray-50 rounded-[3rem] border-2 border-dashed border-gray-100">
                <div class="w-20 h-20 bg-white rounded-3xl shadow-sm border border-gray-100 flex items-center justify-center mx-auto mb-6">
                    <i class="fa-solid fa-photo-film text-4xl text-gray-200"></i>
                </div>
                <h3 class="text-lg font-black text-gray-900 uppercase tracking-widest leading-none">Discovery Empty</h3>
                <p class="text-[10px] text-gray-500 font-bold uppercase mt-2 tracking-wider">No media files match your current search criteria.</p>
                <button @click="search = ''" class="mt-6 px-6 py-2 bg-indigo-600 text-white text-[10px] font-black uppercase rounded-xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">Reset Search</button>
            </div>
        </template>
    </div>

    <!-- Lightbox Modal -->
    <div x-show="showLightbox" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[999] bg-gray-950/95 backdrop-blur-xl flex flex-col items-center justify-center p-4 md:p-12"
         style="display: none;"
         @keydown.escape.window="showLightbox = false">
        
        <!-- Controls -->
        <div class="absolute top-8 right-8 flex gap-4">
            <a :href="activeMedia ? '/storage/' + activeMedia.path : '#'" download class="w-12 h-12 bg-white/10 hover:bg-white/20 border border-white/10 rounded-2xl flex items-center justify-center text-white transition-all">
                <i class="fa-solid fa-download text-xl"></i>
            </a>
            <button @click="showLightbox = false" class="w-12 h-12 bg-white/10 hover:bg-rose-600 border border-white/10 rounded-2xl flex items-center justify-center text-white transition-all">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <!-- Content Area -->
        <div class="w-full h-full flex items-center justify-center" @click.self="showLightbox = false">
            <template x-if="activeMedia && activeMedia.type === 'image'">
                <img :src="'/storage/' + activeMedia.path" class="max-w-full max-h-full rounded-2xl shadow-2xl border-4 border-white/10" alt="Preview">
            </template>
            <template x-if="activeMedia && activeMedia.type === 'video'">
                <video controls class="max-w-full max-h-full rounded-2xl shadow-2xl border-4 border-white/10" :src="'/storage/' + activeMedia.path"></video>
            </template>
            <template x-if="activeMedia && activeMedia.type === 'audio'">
                <div class="bg-indigo-900/50 p-12 rounded-[3rem] border border-white/10 flex flex-col items-center w-full max-w-lg">
                    <div class="w-24 h-24 bg-white/10 rounded-full flex items-center justify-center text-white mb-8 border border-white/10 animate-pulse">
                        <i class="fa-solid fa-microphone text-4xl"></i>
                    </div>
                    <audio controls class="w-full" :src="'/storage/' + activeMedia.path"></audio>
                </div>
            </template>
        </div>

        <!-- Info Footer -->
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 bg-white/5 backdrop-blur-md px-8 py-4 rounded-2xl border border-white/10 text-center">
            <h6 class="text-white font-black text-sm tracking-tight mb-1" x-text="activeMedia ? activeMedia.path.split('/').pop() : ''"></h6>
            <div class="text-indigo-300 font-bold uppercase tracking-[0.2em] text-[10px]">Submitted on <span x-text="activeMedia ? activeMedia.date : ''"></span></div>
        </div>
    </div>
</div>
@endsection
