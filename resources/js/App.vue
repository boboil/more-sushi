<template>
    <div>
        <b-card title="Card Title" no-body>
            <b-card-header header-tag="nav">
                <b-nav card-header tabs>
                    <!-- <b-nav-item>'s with child routes. Note the trailing slash on the first <b-nav-item> -->
                    <b-nav-item to="/" exact exact-active-class="active">Заказ</b-nav-item>
                    <b-nav-item to="/today-orders" exact exact-active-class="active">Сегодняшние заказы</b-nav-item>
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
export default {}
</script>
