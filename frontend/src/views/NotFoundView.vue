<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="text-center animate-fade-in">
      <div class="w-16 h-16 mx-auto rounded-lg bg-gray-100 flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-gray-950 mb-2">Página no encontrada</h1>
      <p class="text-gray-500 mb-6">La página que buscas no existe o fue movida.</p>
      <router-link to="/" class="btn btn-primary">
        Volver al inicio
      </router-link>
    </div>
  </div>
</template>
