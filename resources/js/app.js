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

];

const router = new VueRouter({ mode: 'history', routes: routes});
new Vue(Vue.util.extend({ router }, App)).$mount('#app');
