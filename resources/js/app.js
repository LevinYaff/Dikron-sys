import './bootstrap';
import '../css/app.css';

import { createApp } from 'vue';
import { createPinia } from 'pinia';

// Componentes base
import AppLayout from './components/Layout/AppLayout.vue';
import Sidebar from './components/Layout/Sidebar.vue';
import Header from './components/Layout/Header.vue';
import ConsultaPersona from './components/Forms/ConsultaPersona.vue';
import RegistroPersona from './components/Forms/RegistroPersona.vue';
import ListaEntregas from './components/Tables/ListaEntregas.vue';
import LoadingSpinner from './components/Utils/LoadingSpinner.vue';
import BadgeEstado from './components/Utils/BadgeEstado.vue';

const app = createApp({});
const pinia = createPinia();

// Instalar Pinia
app.use(pinia);

// Registrar componentes globales
app.component('AppLayout', AppLayout);
app.component('Sidebar', Sidebar);
app.component('Header', Header);
app.component('ConsultaPersona', ConsultaPersona);
app.component('RegistroPersona', RegistroPersona);
app.component('ListaEntregas', ListaEntregas);
app.component('LoadingSpinner', LoadingSpinner);
app.component('BadgeEstado', BadgeEstado);

// Configurar Echo para WebSockets
if (window.Echo) {
    // Configuración de canales se hará más adelante
    console.log('Laravel Echo configurado correctamente');
}

// Configuración global de axios
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// CSRF Token
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// Montar la aplicación
app.mount('#app');
