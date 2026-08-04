@extends('layouts.app')

@section('title', __('AI Media Transcription | Audio & Video to Text'))
@section('meta_description', 'Transcribe audio and video recordings or uploaded files into high-accuracy text powered.')

@section('content')
    <div x-data="{
                                activeTab: 'upload', // 'upload' or 'record'
                                recordMode: 'audio', // 'audio' or 'video'

                                // File Upload state
                                selectedFile: null,
                                filePreviewUrl: null,
                                isDragOver: false,

                                // Live Recording state
                                isRecording: false,
                                isPaused: false,
                                recordTimer: 0,
                                timerInterval: null,
                                mediaRecorder: null,
                                recordedChunks: [],
                                recordedBlob: null,
                                recordedMediaUrl: null,
                                recordingStream: null,

                                // Processing & Results
                                isProcessing: false,
                                transcriptionText: '',
                                wordCount: 0,
                                charCount: 0,
                                searchQuery: '',

                                init() {
                                    this.$watch('activeTab', (val) => {
                                        if (val !== 'record' && this.recordingStream) {
                                            this.stopStream();
                                        }
                                    });
                                },

                                handleFileDrop(e) {
                                    this.isDragOver = false;
                                    const files = e.dataTransfer?.files;
                                    if (files && files.length > 0) {
                                        this.processSelectedFile(files[0]);
                                    }
                                },

                                handleFileSelect(e) {
                                    const files = e.target.files;
                                    if (files && files.length > 0) {
                                        this.processSelectedFile(files[0]);
                                    }
                                },

                                processSelectedFile(file) {
                                    const validTypes = ['audio/', 'video/', 'application/ogg'];
                                    const isValid = validTypes.some(t => file.type.startsWith(t)) || 
                                        /\.(mp3|mp4|wav|webm|m4a|ogg|mpeg|aac|flac|mov)$/i.test(file.name);

                                    if (!isValid) {
                                        Swal.fire({
                                            title: 'Invalid File Type',
                                            text: 'Please upload a valid audio or video file (MP3, MP4, WAV, WEBM, M4A, OGG, FLAC, MOV).',
                                            icon: 'warning',
                                            customClass: { popup: 'rounded-3xl' }
                                        });
                                        return;
                                    }

                                    if (file.size > 52428800) { // 50MB
                                        Swal.fire({
                                            title: 'File Too Large',
                                            text: 'Maximum file size allowed is 50MB.',
                                            icon: 'warning',
                                            customClass: { popup: 'rounded-3xl' }
                                        });
                                        return;
                                    }

                                    this.selectedFile = file;
                                    if (this.filePreviewUrl) URL.revokeObjectURL(this.filePreviewUrl);
                                    this.filePreviewUrl = URL.createObjectURL(file);
                                },

                                clearSelectedFile() {
                                    this.selectedFile = null;
                                    if (this.filePreviewUrl) {
                                        URL.revokeObjectURL(this.filePreviewUrl);
                                        this.filePreviewUrl = null;
                                    }
                                    if (this.$refs.fileInput) {
                                        this.$refs.fileInput.value = '';
                                    }
                                },

                                // Live Recording methods
                                async startRecording() {
                                    this.recordedChunks = [];
                                    this.recordedBlob = null;
                                    if (this.recordedMediaUrl) {
                                        URL.revokeObjectURL(this.recordedMediaUrl);
                                        this.recordedMediaUrl = null;
                                    }

                                    try {
                                        const constraints = this.recordMode === 'video' 
                                            ? { audio: true, video: { width: { ideal: 1280 }, height: { ideal: 720 } } }
                                            : { audio: true, video: false };

                                        this.recordingStream = await navigator.mediaDevices.getUserMedia(constraints);

                                        if (this.recordMode === 'video' && this.$refs.livePreview) {
                                            this.$refs.livePreview.srcObject = this.recordingStream;
                                            this.$refs.livePreview.play();
                                        }

                                        let mimeType = 'audio/webm';
                                        if (this.recordMode === 'video') {
                                            mimeType = MediaRecorder.isTypeSupported('video/webm;codecs=vp9') ? 'video/webm;codecs=vp9' : 'video/webm';
                                        } else if (MediaRecorder.isTypeSupported('audio/webm')) {
                                            mimeType = 'audio/webm';
                                        } else if (MediaRecorder.isTypeSupported('audio/mp4')) {
                                            mimeType = 'audio/mp4';
                                        }

                                        this.mediaRecorder = new MediaRecorder(this.recordingStream, { mimeType });

                                        this.mediaRecorder.ondataavailable = (e) => {
                                            if (e.data && e.data.size > 0) {
                                                this.recordedChunks.push(e.data);
                                            }
                                        };

                                        this.mediaRecorder.onstop = () => {
                                            this.recordedBlob = new Blob(this.recordedChunks, { type: mimeType });
                                            this.recordedMediaUrl = URL.createObjectURL(this.recordedBlob);
                                            this.stopStream();
                                        };

                                        this.mediaRecorder.start(1000);
                                        this.isRecording = true;
                                        this.isPaused = false;
                                        this.startTimer();
                                    } catch (err) {
                                        console.error(err);
                                        Swal.fire({
                                            title: 'Permission Required',
                                            text: 'Could not access ' + (this.recordMode === 'video' ? 'camera and microphone' : 'microphone') + '. Please grant browser permissions.',
                                            icon: 'error',
                                            customClass: { popup: 'rounded-3xl' }
                                        });
                                    }
                                },

                                pauseRecording() {
                                    if (this.mediaRecorder && this.isRecording) {
                                        if (this.isPaused) {
                                            this.mediaRecorder.resume();
                                            this.isPaused = false;
                                        } else {
                                            this.mediaRecorder.pause();
                                            this.isPaused = true;
                                        }
                                    }
                                },

                                stopRecording() {
                                    if (this.mediaRecorder && this.isRecording) {
                                        this.mediaRecorder.stop();
                                        this.isRecording = false;
                                        this.isPaused = false;
                                        this.stopTimer();
                                    }
                                },

                                stopStream() {
                                    if (this.recordingStream) {
                                        this.recordingStream.getTracks().forEach(track => track.stop());
                                        this.recordingStream = null;
                                    }
                                    if (this.$refs.livePreview) {
                                        this.$refs.livePreview.srcObject = null;
                                    }
                                },

                                discardRecording() {
                                    this.stopRecording();
                                    this.recordedChunks = [];
                                    this.recordedBlob = null;
                                    if (this.recordedMediaUrl) {
                                        URL.revokeObjectURL(this.recordedMediaUrl);
                                        this.recordedMediaUrl = null;
                                    }
                                    this.recordTimer = 0;
                                },

                                startTimer() {
                                    this.recordTimer = 0;
                                    clearInterval(this.timerInterval);
                                    this.timerInterval = setInterval(() => {
                                        if (!this.isPaused) {
                                            this.recordTimer++;
                                        }
                                    }, 1000);
                                },

                                stopTimer() {
                                    clearInterval(this.timerInterval);
                                },

                                formatTime(seconds) {
                                    const mins = Math.floor(seconds / 60);
                                    const secs = seconds % 60;
                                    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                                },

                                // Submission & API Call
                                async submitForTranscription(sourceType) {
                                    let fileToSend = null;
                                    if (sourceType === 'file') {
                                        fileToSend = this.selectedFile;
                                    } else if (sourceType === 'record') {
                                        if (!this.recordedBlob) return;
                                        const ext = this.recordMode === 'video' ? 'webm' : 'webm';
                                        fileToSend = new File([this.recordedBlob], `recording_${Date.now()}.${ext}`, { type: this.recordedBlob.type });
                                    }

                                    if (!fileToSend) {
                                        Swal.fire({ title: 'No Media Found', text: 'Please select a file or record audio/video first.', icon: 'warning' });
                                        return;
                                    }

                                    this.isProcessing = true;
                                    const formData = new FormData();
                                    formData.append('file', fileToSend);

                                    try {
                                        const response = await fetch('{{ route('transcription.transcribe') }}', {
                                            method: 'POST',
                                            headers: {
                                                'Accept': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                                            },
                                            body: formData
                                        });

                                        const data = await response.json();

                                        if (!response.ok || !data.success) {
                                            throw new Error(data.message || 'Transcription failed.');
                                        }

                                        this.transcriptionText = data.transcription;
                                        this.wordCount = data.word_count || (data.transcription.trim().match(/\s+/g) || []).length + 1;
                                        this.charCount = data.char_count || data.transcription.length;

                                        Swal.fire({
                                            title: 'Transcription Complete!',
                                            text: 'Text extracted successfully.',
                                            icon: 'success',
                                            toast: true,
                                            position: 'top-end',
                                            showConfirmButton: false,
                                            timer: 3000
                                        });
                                    } catch (err) {
                                        console.error(err);
                                        Swal.fire({
                                            title: 'Transcription Error',
                                            text: err.message || 'Failed to process media file. Please try again.',
                                            icon: 'error',
                                            customClass: { popup: 'rounded-3xl' }
                                        });
                                    } finally {
                                        this.isProcessing = false;
                                    }
                                },

                                downloadTxt() {
                                    if (!this.transcriptionText) return;
                                    const blob = new Blob([this.transcriptionText], { type: 'text/plain;charset=utf-8' });
                                    const url = URL.createObjectURL(blob);
                                    const a = document.createElement('a');
                                    a.href = url;
                                    a.download = `transcription_${Date.now()}.txt`;
                                    a.click();
                                    URL.revokeObjectURL(url);
                                }
                            }"
        class="min-h-screen bg-[#1d2327] text-slate-100 border border-white/5 shadow-2xl flex flex-col font-sans">

        <!-- Header -->
        <div
            class="px-8 py-6 border-b border-white/5 bg-black/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div
                    class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-[#2271b1] flex items-center justify-center shadow-lg shadow-[#2271b1]/20">
                    <i class="fa-solid fa-microphone-lines text-lg text-white"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black tracking-tight text-white">{{ __('AI Media Transcription') }}</h3>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">
                        {{ __('Transcribe audio & video files or live browser recordings into accurate text') }}
                    </p>
                </div>
            </div>

            <!-- Mode Selector Tabs -->
            <div class="flex bg-black/40 p-1 rounded-2xl border border-white/5">
                <button @click="activeTab = 'upload'"
                    :class="activeTab === 'upload' ? 'bg-[#2271b1] text-white shadow-md' : 'text-slate-400 hover:text-white'"
                    class="px-5 py-2 rounded-xl text-xs font-black tracking-wider transition-all flex items-center gap-2">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    {{ __('Upload File') }}
                </button>
                <button @click="activeTab = 'record'"
                    :class="activeTab === 'record' ? 'bg-[#2271b1] text-white shadow-md' : 'text-slate-400 hover:text-white'"
                    class="px-5 py-2 rounded-xl text-xs font-black tracking-wider transition-all flex items-center gap-2">
                    <i class="fa-solid fa-circle text-red-400 animate-pulse"></i>
                    {{ __('Live Recorder') }}
                </button>
            </div>
        </div>

        <!-- Main Workspace: 2 Column Layout (Left: Source Media Input | Right: Transcription Result) -->
        <div class="flex-1 p-8 grid lg:grid-cols-2 gap-8 items-start bg-[#1d2327]">

            <!-- ═══════════════════════════════════════ LEFT PANEL: MEDIA INPUT ═══════════════════════════════════════ -->
            <div class="flex flex-col gap-6">

                <!-- TAB 1: FILE UPLOAD -->
                <div x-show="activeTab === 'upload'"
                    class="rounded-2xl border border-white/5 bg-black/20 p-6 flex flex-col gap-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black tracking-wider text-slate-400">{{ __('Media File Upload') }}</span>
                        <span
                            class="text-[10px] text-slate-400 font-bold">{{ __('Max 50MB (MP3, MP4, WAV, WEBM, M4A, OGG)') }}</span>
                    </div>

                    <!-- Dropzone -->
                    <div @dragover.prevent="isDragOver = true" @dragleave.prevent="isDragOver = false"
                        @drop.prevent="handleFileDrop($event)" @click="$refs.fileInput.click()"
                        :class="isDragOver ? 'border-[#2271b1] bg-[#2271b1]/10' : 'border-white/10 hover:border-[#2271b1]/50 bg-black/30'"
                        class="border-2 border-dashed rounded-2xl p-8 flex flex-col items-center justify-center gap-4 cursor-pointer transition-all duration-200 min-h-[240px]">

                        <input type="file" x-ref="fileInput" @change="handleFileSelect($event)" class="hidden"
                            accept="audio/*,video/*,.mp3,.mp4,.wav,.webm,.m4a,.ogg,.flac,.mov">

                        <div
                            class="w-14 h-14 rounded-full bg-[#2271b1]/10 flex items-center justify-center border border-[#2271b1]/20">
                            <i class="fa-solid fa-[#2271b1] text-2xl text-[#2271b1]"
                                :class="selectedFile ? 'fa-file-audio' : 'fa-cloud-arrow-up'"></i>
                        </div>

                        <template x-if="!selectedFile">
                            <div class="text-center flex flex-col gap-1">
                                <span
                                    class="text-sm font-black text-slate-200">{{ __('Drag & drop your audio or video file here') }}</span>
                                <span class="text-xs text-slate-400">{{ __('or click to browse from your device') }}</span>
                            </div>
                        </template>

                        <template x-if="selectedFile">
                            <div class="text-center flex flex-col items-center gap-2">
                                <span class="text-sm font-black text-slate-500 truncate max-w-xs"
                                    x-text="selectedFile.name"></span>
                                <span class="text-xs text-slate-400 font-mono"
                                    x-text="`${(selectedFile.size / (1024 * 1024)).toFixed(2)} MB | ${selectedFile.type || 'Media File'}`"></span>
                                <button @click.stop="clearSelectedFile()"
                                    class="mt-2 text-[10px] font-bold text-red-400 hover:text-red-300 transition-colors flex items-center gap-1">
                                    <i class="fa-solid fa-trash-can"></i> {{ __('Remove File') }}
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- File Preview Player -->
                    <template x-if="selectedFile && filePreviewUrl">
                        <div class="bg-black/40 rounded-xl p-4 border border-white/5 flex flex-col gap-3">
                            <span
                                class="text-[10px] font-black tracking-wider text-slate-400">{{ __('Media Preview') }}</span>

                            <template
                                x-if="selectedFile.type.startsWith('video') || selectedFile.name.match(/\.(mp4|webm|mov)$/i)">
                                <video :src="filePreviewUrl" controls class="w-full rounded-lg max-h-56 bg-black"></video>
                            </template>

                            <template
                                x-if="selectedFile.type.startsWith('audio') || selectedFile.name.match(/\.(mp3|wav|m4a|ogg|flac)$/i)">
                                <audio :src="filePreviewUrl" controls class="w-full"></audio>
                            </template>
                        </div>
                    </template>

                    <!-- Action Button -->
                    <button @click="submitForTranscription('file')" :disabled="!selectedFile || isProcessing"
                        class="w-full py-4 bg-[#2271b1] hover:bg-blue-600 disabled:opacity-30 text-white rounded-xl text-xs font-black tracking-widest transition-all shadow-lg shadow-[#2271b1]/10 flex items-center justify-center gap-2">

                        <span
                            x-text="isProcessing ? '{{ __('Transcribing Media...') }}' : '{{ __('Transcribe File Now') }}'"></span>
                    </button>
                </div>

                <!-- TAB 2: LIVE RECORDER -->
                <div x-show="activeTab === 'record'"
                    class="rounded-2xl border border-white/5 bg-black/20 p-6 flex flex-col gap-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-black tracking-wider text-slate-400">{{ __('Browser Live Recorder') }}</span>

                        <!-- Record Mode Selection -->
                        <div class="flex bg-black/30 p-0.5 rounded-lg border border-white/5">
                            <button @click="recordMode = 'audio'; stopStream();" :disabled="isRecording"
                                :class="recordMode === 'audio' ? 'bg-[#2271b1] text-white' : 'text-slate-400 hover:text-slate-200'"
                                class="px-3 py-1 rounded-md text-[10px] font-bold transition-all">
                                <i class="fa-solid fa-microphone mr-1"></i> {{ __('Audio Only') }}
                            </button>
                            <button @click="recordMode = 'video'; stopStream();" :disabled="isRecording"
                                :class="recordMode === 'video' ? 'bg-[#2271b1] text-white' : 'text-slate-400 hover:text-slate-200'"
                                class="px-3 py-1 rounded-md text-[10px] font-bold transition-all">
                                <i class="fa-solid fa-video mr-1"></i> {{ __('Audio + Video') }}
                            </button>
                        </div>
                    </div>

                    <!-- Live Video Viewfinder / Recording Interface -->
                    <div
                        class="relative bg-black/50 rounded-2xl border border-white/5 overflow-hidden min-h-[220px] flex flex-col items-center justify-center p-4">

                        <!-- Webcam Feed when recording video -->
                        <video x-ref="livePreview" x-show="recordMode === 'video' && isRecording" autoplay muted playsinline
                            class="w-full h-56 rounded-xl object-cover bg-black"></video>

                        <!-- Audio Pulse Visualizer when recording audio -->
                        <div x-show="recordMode === 'audio' && isRecording" class="flex flex-col items-center gap-4 py-8">
                            <div class="relative w-20 h-20 flex items-center justify-center">
                                <span class="absolute inset-0 rounded-full bg-red-500/20 animate-ping"></span>
                                <div
                                    class="w-16 h-16 rounded-full bg-gradient-to-tr from-red-600 to-rose-500 flex items-center justify-center shadow-lg shadow-red-500/30">
                                    <i class="fa-solid fa-microphone text-2xl text-white"></i>
                                </div>
                            </div>
                            <span
                                class="text-xs text-red-400 font-black tracking-widest animate-pulse">{{ __('Recording audio...') }}</span>
                        </div>

                        <!-- Idle State Placeholder -->
                        <div x-show="!isRecording && !recordedMediaUrl"
                            class="flex flex-col items-center gap-3 py-8 text-slate-500">
                            <i class="fa-solid text-4xl opacity-30"
                                :class="recordMode === 'video' ? 'fa-video' : 'fa-microphone-lines'"></i>
                            <span class="text-xs font-bold text-slate-400"
                                x-text="recordMode === 'video' ? '{{ __('Click start to enable webcam & microphone') }}' : '{{ __('Click start to record audio from your microphone') }}'"></span>
                        </div>

                        <!-- Recorded Media Player Preview -->
                        <div x-show="!isRecording && recordedMediaUrl" class="w-full flex flex-col gap-3">
                            <span class="text-[10px] font-black tracking-wider text-slate-400 flex items-center gap-1">
                                <i class="fa-solid fa-circle-check"></i> {{ __('Recording Complete - Preview') }}
                            </span>
                            <template x-if="recordMode === 'video'">
                                <video :src="recordedMediaUrl" controls class="w-full rounded-xl max-h-56 bg-black"></video>
                            </template>
                            <template x-if="recordMode === 'audio'">
                                <audio :src="recordedMediaUrl" controls class="w-full"></audio>
                            </template>
                        </div>

                        <!-- Timer Badge -->
                        <div x-show="isRecording"
                            class="absolute top-4 right-4 bg-black/80 px-3 py-1 rounded-full border border-red-500/40 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                            <span class="text-xs font-mono font-black text-white" x-text="formatTime(recordTimer)"></span>
                        </div>
                    </div>

                    <!-- Recording Controls -->
                    <div class="flex items-center justify-center gap-3">
                        <!-- Start Button -->
                        <button x-show="!isRecording && !recordedBlob" @click="startRecording()"
                            class="px-6 py-3 bg-red-600 hover:bg-red-500 text-white rounded-xl text-xs font-black tracking-wider transition-all shadow-lg shadow-red-600/20 flex items-center gap-2">
                            <i class="fa-solid fa-circle text-xs"></i>
                            {{ __('Start Recording') }}
                        </button>

                        <!-- Pause/Resume Button -->
                        <button x-show="isRecording" @click="pauseRecording()"
                            class="px-4 py-3 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-xs font-black tracking-wider transition-all flex items-center gap-2">
                            <i class="fa-solid" :class="isPaused ? 'fa-play' : 'fa-pause'"></i>
                            <span x-text="isPaused ? '{{ __('Resume') }}' : '{{ __('Pause') }}'"></span>
                        </button>

                        <!-- Stop Button -->
                        <button x-show="isRecording" @click="stopRecording()"
                            class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl text-xs font-black tracking-wider transition-all flex items-center gap-2">
                            <i class="fa-solid fa-square text-xs text-red-400"></i>
                            {{ __('Stop Recording') }}
                        </button>

                        <!-- Re-record / Discard Button -->
                        <button x-show="!isRecording && recordedBlob" @click="discardRecording()"
                            class="px-4 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                            <i class="fa-solid fa-rotate-left"></i>
                            {{ __('Discard & Re-record') }}
                        </button>
                    </div>

                    <!-- Action Button for Recording -->
                    <button x-show="recordedBlob && !isRecording" @click="submitForTranscription('record')"
                        :disabled="isProcessing"
                        class="w-full py-4 bg-[#2271b1] hover:bg-blue-600 disabled:opacity-30 text-white rounded-xl text-xs font-black tracking-widest transition-all shadow-lg shadow-[#2271b1]/10 flex items-center justify-center gap-2 mt-2">
                        <span
                            x-text="isProcessing ? '{{ __('Transcribing Recording...') }}' : '{{ __('Transcribe Recording Now') }}'"></span>
                    </button>
                </div>
            </div>

            <!-- ═══════════════════════════════════════ RIGHT PANEL: TRANSCRIPTION RESULTS ═══════════════════════════════════════ -->
            <div class="flex flex-col gap-6">
                <div class="rounded-2xl border border-white/5 bg-black/20 p-6 flex flex-col gap-4 shadow-sm relative">

                    <!-- Result Header & Actions -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3 mr-2">
                            <span
                                class="text-xs font-black tracking-wider text-slate-500">{{ __('Transcription Output') }}</span>
                            <span x-show="transcriptionText"
                                class="text-[10px] text-slate-400 font-mono bg-white/5 px-2 py-0.5 rounded-md"
                                x-text="`${wordCount} {{ __('words') }} | ${charCount} {{ __('chars') }}`"></span>
                        </div>

                        <div class="flex items-center gap-2" x-show="transcriptionText">
                            <!-- Copy Button -->
                            <button
                                @click="navigator.clipboard.writeText(transcriptionText); Swal.fire({title: 'Copied!', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 1500})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-[10px] font-bold text-slate-300 transition-colors">
                                <i class="fa-regular fa-copy"></i>
                                {{ __('Copy') }}
                            </button>

                            <!-- Download Text -->
                            <button @click="downloadTxt()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-[10px] font-bold text-slate-300 transition-colors">
                                <i class="fa-solid fa-file-arrow-down"></i>
                                {{ __('Download') }}
                            </button>

                            <!-- Clear -->
                            <button @click="transcriptionText = ''; wordCount = 0; charCount = 0;"
                                class="text-[10px] text-slate-500 hover:text-white font-bold px-2 py-1 transition-colors">
                                {{ __('Clear') }}
                            </button>
                        </div>
                    </div>

                    <!-- Output Area Container -->
                    <div class="relative">
                        <!-- Loading Overlay -->
                        <div x-show="isProcessing"
                            class="absolute inset-0 bg-[#1d2327]/95 flex flex-col items-center justify-center gap-3 z-30 animate-in fade-in duration-150 rounded-xl">
                            <div class="relative w-12 h-12 flex items-center justify-center">
                                <span class="absolute w-full h-full border-2 border-[#2271b1]/20 rounded-full"></span>
                                <span
                                    class="absolute w-full h-full border-2 border-t-[#2271b1] rounded-full animate-spin"></span>
                            </div>
                            <span
                                class="text-xs text-slate-300 font-black tracking-widest mt-2">{{ __('Transcribing audio...') }}</span>
                            <span
                                class="text-[9px] text-slate-500">{{ __('Processing timestamps, speech patterns and vocabulary...') }}</span>
                        </div>

                        <!-- Empty State Placeholder -->
                        <div x-show="!transcriptionText && !isProcessing"
                            class="w-full h-[420px] bg-black/30 border border-white/5 rounded-xl flex flex-col items-center justify-center text-slate-500 gap-3 p-6 text-center">
                            <i class="fa-solid fa-quote-left text-3xl opacity-30"></i>
                            <span
                                class="text-xs font-bold text-slate-400 tracking-tight">{{ __('Your transcribed text will appear here') }}</span>
                            <span
                                class="text-[10px] text-slate-500 max-w-xs">{{ __('Upload an audio/video file or record directly using your browser.') }}</span>
                        </div>

                        <!-- Editable Transcript Textarea -->
                        <textarea x-show="transcriptionText || isProcessing" x-model="transcriptionText"
                            @input="wordCount = (transcriptionText.trim().match(/\s+/g) || []).length + (transcriptionText.trim() ? 1 : 0); charCount = transcriptionText.length;"
                            placeholder="{{ __('Transcription result will appear here. You can edit or refine text directly...') }}"
                            class="w-full h-[420px] bg-black/30 border border-white/5 rounded-xl p-5 text-sm text-slate-200 leading-relaxed custom-scrollbar resize-none focus:outline-none focus:border-[#2271b1]/30 focus:ring-1 focus:ring-[#2271b1]/10 transition-all font-sans"></textarea>
                    </div>

                    <!-- Transcript Info Footer -->
                    <div x-show="transcriptionText"
                        class="flex items-center justify-between text-[10px] text-slate-500 border-t border-white/5 pt-3">
                        <span>{{ __('You can edit the text directly before downloading or copying.') }}</span>
                    </div>

                </div>
            </div>

        </div>
    </div>
@endsection