<template>
    <div>
        <b-card title="Card Title" no-body>
            <b-card-header header-tag="nav">
                <b-nav card-header tabs>
                    <!-- <b-nav-item>'s with child routes. Note the trailing slash on the first <b-nav-item> -->
                    <b-nav-item to="/" exact exact-active-class="active">Заказ</b-nav-item>
                    <b-nav-item to="/today-orders" exact exact-active-class="active">Текущие  заказы</b-nav-item>
                    <b-nav-item to="/yesterday-orders" exact exact-active-class="active">Заказы к выдаче</b-nav-item>
                    <router-link to="/login" class="nav-item nav-link">login</router-link>
                    <router-link to="/products" class="nav-item nav-link" v-if="isLoggedIn">Ролы</router-link>
                </b-nav>
            </b-card-header>

            <b-card-body>
                <!-- Child route gets rendered in <router-view> or <nuxt-child> -->
                <router-view></router-view>
                <!-- Or if using Nuxt.js
                <nuxt-child></nuxt-child>
                -->
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
        }
    },
    created() {
        if (window.Laravel.isLoggedin) {
            this.isLoggedIn = true
        }
    },
    methods: {
        logout(e) {
            console.log('ss')
            e.preventDefault()
            this.$axios.get('/sanctum/csrf-cookie').then(response => {
                this.$axios.post('/api/logout')
                    .then(response => {
                        if (response.data.success) {
                            window.location.href = "/"
                        } else {
                            console.log(response)
                        }
                    })
                    .catch(function (error) {
                        console.error(error);
                    });
            })
        }
    },
}
</script>
