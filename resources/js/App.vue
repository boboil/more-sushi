<template>
    <div>
        <b-card title="Card Title" no-body>
            <b-card-header header-tag="nav">
                <b-nav card-header tabs>
                    <!-- <b-nav-item>'s with child routes. Note the trailing slash on the first <b-nav-item> -->
                    <b-nav-item to="/" exact exact-active-class="active">Заказ</b-nav-item>
                    <b-nav-item to="/today-orders" exact exact-active-class="active">Текущие  заказы</b-nav-item>
                    <b-nav-item to="/yesterday-orders" exact exact-active-class="active">Заказы к выдаче</b-nav-item>
                    <router-link to="/login" class="nav-item nav-link" v-if="!isLoggedIn">Login</router-link>
                    <router-link to="/products" class="nav-item nav-link" v-if="isLoggedIn">Ролы</router-link>
                    <router-link to="/current-orders" class="nav-item nav-link" v-if="isLoggedIn">Текущие заказы</router-link>
                    <router-link to="/my-hours" class="nav-item nav-link" v-if="isLoggedIn">Мої години</router-link>
                    <a href="/admin" class="nav-item nav-link" v-if="isSuperAdmin">Admin Panel</a>
                    <a href="#" class="nav-item nav-link ml-auto" v-if="isLoggedIn" @click="logout">Вийти</a>
                </b-nav>
            </b-card-header>

            <b-card-body>
                <router-view></router-view>
            </b-card-body>
        </b-card>
    </div>
</template>

<script>
export default {
    name: "App",
    data() {
        return {
            isLoggedIn: false,
            isSuperAdmin: false,
        }
    },
    created() {
        if (window.Laravel.isLoggedin) {
            this.isLoggedIn = true
            this.isSuperAdmin = !!window.Laravel.isSuperAdmin
        }
    },
    methods: {
        logout(e) {
            e.preventDefault()
            const meta = document.querySelector('meta[name="csrf-token"]')
            this.$axios.post('/logout', {}, { headers: { 'X-CSRF-TOKEN': meta ? meta.content : '', Accept: 'application/json' } })
                .catch(() => {})
                .finally(() => { window.location.href = '/login' })
        }
    },
}
</script>
