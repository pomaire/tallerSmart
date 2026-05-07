<?php

/** @var yii\web\View $this */
/** @var array $modulos */

$this->title = 'Manual de Usuario - TallerSmart';
?>

<div class="min-h-screen bg-gray-50" x-data="manualApp()" x-init="init()">
    
    <!-- Header del Manual -->
    <div class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <h1 class="text-2xl font-bold text-gray-900">Manual de Usuario</h1>
                </div>
                
                <!-- Buscador -->
                <div class="relative flex-1 max-w-lg ml-8">
                    <input 
                        type="text" 
                        x-model="busqueda"
                        @input="filtrarContenido()"
                        placeholder="Buscar en el manual..." 
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                
                <button @click="descargarPDF()" class="ml-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Descargar PDF
                </button>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex gap-8">
            
            <!-- Sidebar de Navegación -->
            <aside class="w-64 flex-shrink-0">
                <nav class="sticky top-24 space-y-1">
                    <template x-for="(modulo, index) in modulos" :key="index">
                        <a 
                            :href="'#modulo-' + index"
                            @click.prevent="seleccionarModulo(index)"
                            class="block px-4 py-3 rounded-lg text-sm font-medium transition-colors"
                            :class="moduloActivo === index ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'"
                            x-text="modulo.titulo"
                        ></a>
                    </template>
                </nav>
            </aside>

            <!-- Contenido Principal -->
            <main class="flex-1 min-w-0">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    
                    <!-- Introducción -->
                    <div class="px-8 py-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Bienvenido a TallerSmart</h2>
                        <p class="text-gray-600">Sistema integral de gestión de talleres mecánicos</p>
                        <div class="mt-4 flex items-center gap-4 text-sm text-gray-500">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Última actualización: Mayo 2026
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Versión 1.0.0
                            </span>
                        </div>
                    </div>

                    <!-- Módulos del Sistema -->
                    <div class="divide-y divide-gray-200">
                        <template x-for="(modulo, modIndex) in modulosFiltrados" :key="modIndex">
                            <div :id="'modulo-' + modIndex" class="scroll-mt-24">
                                <div class="px-8 py-6">
                                    <div class="flex items-center gap-3 mb-4">
                                        <span class="flex items-center justify-center w-10 h-10 rounded-lg text-white font-bold text-lg"
                                              :class="obtenerColor(modIndex)">
                                            <span x-text="modIndex + 1"></span>
                                        </span>
                                        <h3 class="text-xl font-bold text-gray-900" x-text="modulo.titulo"></h3>
                                    </div>
                                    
                                    <div class="prose prose-blue max-w-none">
                                        <div class="text-gray-600 mb-4" x-html="modulo.descripcion"></div>
                                        
                                        <!-- Características Principales -->
                                        <div class="mb-6">
                                            <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Características Principales
                                            </h4>
                                            <ul class="space-y-2">
                                                <template x-for="caracteristica in modulo.caracteristicas" :key="caracteristica">
                                                    <li class="flex items-start gap-2 text-gray-700">
                                                        <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        <span x-text="caracteristica"></span>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                        
                                        <!-- Pasos de Uso -->
                                        <div class="mb-6">
                                            <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                </svg>
                                                ¿Cómo usarlo?
                                            </h4>
                                            <div class="space-y-4">
                                                <template x-for="(paso, pasoIndex) in modulo.pasos" :key="pasoIndex">
                                                    <div class="flex gap-3">
                                                        <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-blue-100 text-blue-600 font-bold text-sm"
                                                              x-text="pasoIndex + 1">
                                                        </span>
                                                        <div class="flex-1 pt-1">
                                                            <p class="text-gray-700" x-text="paso"></p>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        
                                        <!-- Consejos y Notas -->
                                        <div x-show="modulo.consejos" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                            <h4 class="font-semibold text-yellow-800 mb-2 flex items-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                                </svg>
                                                Consejos Útiles
                                            </h4>
                                            <ul class="space-y-1">
                                                <template x-for="consejo in modulo.consejos" :key="consejo">
                                                    <li class="text-yellow-700 text-sm flex items-start gap-2">
                                                        <span class="text-yellow-500 mt-1">•</span>
                                                        <span x-text="consejo"></span>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                
                <!-- Footer del Manual -->
                <div class="mt-8 text-center text-gray-500 text-sm">
                    <p>¿Necesitas ayuda adicional? Contacta a soporte técnico: <a href="mailto:soporte@tallersmart.cl" class="text-blue-600 hover:underline">soporte@tallersmart.cl</a></p>
                    <p class="mt-2">© 2026 TallerSmart. Todos los derechos reservados.</p>
                </div>
            </main>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('manualApp', () => ({
        busqueda: '',
        moduloActivo: 0,
        modulos: <?= json_encode($modulos, JSON_UNESCAPED_UNICODE) ?>,
        
        get modulosFiltrados() {
            if (!this.busqueda.trim()) {
                return this.modulos;
            }
            const busquedaLower = this.busqueda.toLowerCase();
            return this.modulos.filter(modulo => 
                modulo.titulo.toLowerCase().includes(busquedaLower) ||
                modulo.descripcion.toLowerCase().includes(busquedaLower) ||
                modulo.caracteristicas.some(c => c.toLowerCase().includes(busquedaLower))
            );
        },
        
        init() {
            // Inicialización si es necesaria
        },
        
        seleccionarModulo(index) {
            this.moduloActivo = index;
            window.location.hash = 'modulo-' + index;
        },
        
        obtenerColor(index) {
            const colores = [
                'bg-blue-600', 'bg-green-600', 'bg-orange-600', 'bg-purple-600',
                'bg-red-600', 'bg-indigo-600', 'bg-teal-600', 'bg-yellow-600',
                'bg-emerald-600', 'bg-cyan-600', 'bg-rose-600', 'bg-slate-600', 'bg-zinc-600'
            ];
            return colores[index % colores.length];
        },
        
        filtrarContenido() {
            // El filtrado se hace automáticamente con la propiedad computada modulosFiltrados
        },
        
        descargarPDF() {
            window.print();
        }
    }));
});
</script>
