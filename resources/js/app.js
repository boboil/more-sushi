import YesterdayOrders from "./components/YesterdayOrders";

require("./bootstrap")

import Vue from 'vue';

import VueRouter from 'vue-router';
Vue.use(VueRouter);

window.axios = require('axios');
import { BootstrapVue, IconsPlugin } from 'bootstrap-vue'


// Import Bootstrap and BootstrapVue CSS files (order is important)

import 'bootstrap-vue/dist/bootstrap-vue.css'

// Make BootstrapVue available throughout your project
Vue.use(BootstrapVue)
// Optionally install the BootstrapVue icon components plugin
Vue.use(IconsPlugin)

import App from './App.vue';

import AdminOrder from './components/AdminOrder.vue';
import TodayOrders from './components/TodayOrders.vue';
import ProductsTable from './components/ProductsTable.vue';
import Register from './auth/Register.vue';
import Login from './auth/Login.vue';


const routes = [
    {
        name: 'home',
        path: '/',
        component: AdminOrder
    },
    {
        name: 'today-orders',
        path: '/today-orders',
        component: TodayOrders
    },
    {
        name: 'yesterdayOrders',
        path: '/yesterday-orders',
        component: YesterdayOrders
    },
    {
        name: 'products',
        path: '/products',
        component: ProductsTable
    },
    {
        name: 'login',
        path: '/login',
        component: Login
    },

];
Vue.prototype.$axios = axios
const router = new VueRouter({ mode: 'history', routes: routes});
new Vue(Vue.util.extend({ router }, App)).$mount('#app');
