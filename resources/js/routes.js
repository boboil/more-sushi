import Vue from 'vue'
import VueRouter from 'vue-router'
import AdminOrder from '@/components/AdminOrder.vue';


Vue.use(VueRouter);

const router = new VueRouter({
    mode: 'history',
    routes: [
        {
            path: '/',
            name: 'home',
            component: AdminOrder
        },
        {
            path: '/contact',
            name: 'contact',
            component: Contact
        }
    ]
});

export default router;
