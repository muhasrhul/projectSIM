<div class="filament-widget mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <!-- Header dengan gradient -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="bg-white/20 rounded-lg p-2">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">Export Laporan Arus Kas</h3>
                    <p class="text-green-100 text-sm">Pilih periode dan download laporan dalam format PDF</p>
                </div>
            </div>
        </div>
        
        <!-- Content -->
        <div class="p-6">
            <form wire:submit="exportPDF" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                    <div>
                        {{ $this->form }}
                    </div>
                    <div>
                        <button 
                            type="submit" 
                            class="w-full inline-flex items-center justify-center px-6 py-3 bg-green-600 hover:bg-green-700 border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="font-bold">EXPORT PDF</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Footer info -->
        <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-3 border-t border-gray-200 dark:border-gray-600">
            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Laporan akan terbuka di tab baru dengan dialog print otomatis</span>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('open-url', (event) => {
                window.open(event.url, '_blank');
            });
        });
    </script>
</div>