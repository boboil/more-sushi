<template>
    <div class="row">
        <div class="row">
            <div class="col-12 mb-5">
                <h2>Детально по магазинам</h2>
                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Адресс</th>
                        <th scope="col">Заказ</th>
                        <th scope="col">Бонус</th>
                        <th scope="col">Дата</th>
                        <th scope="col" v-if="auth">Удалить</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(order, key) in orders">
                        <th scope="row">{{ key + 1 }}</th>
                        <td>{{ order.address }}</td>
                        <td>
                <span v-for="product in order.products.data">
                    {{ product.title }} - {{ product.quantity }} шт. <br>
                </span>
                        </td>
                        <td>
                            {{ countBonusRolls(order.sum_product) ? `${countBonusRolls(order.sum_product)} шт` : '-' }}
                        </td>
                        <td>{{ order.order_time }}</td>
                        <td v-if="auth">
                            <button type="button" class="btn btn-danger" @click="deleteOrder(order)">Удалить</button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>


        <div class="row">
            <div class="col-12">
                <h2>Детально по ролам</h2>
                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Название</th>
                        <th scope="col">Колличество</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="product in products">
                        <th scope="row">{{ product.id }}</th>
                        <td>{{ product.title }}</td>
                        <td>{{ product.quantity }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row">
            <div class="col-4">
                Всего без бонуса: {{ sumTodayProducts }}
            </div>
            <div class="col-4">
                Всего c бонусом: {{ countBonusInOrder }}
            </div>
            <div class="col-4">
                Бонусных: {{ countBonus }}
            </div>
        </div>
    </div>

</template>

<script>
export default {
    name: "TodayOrders",
    data() {
        return {
            orders: [],
            loading: false,
            products: [],
            auth: false,
        }
    },
    computed: {
        sumTodayProducts() {
            let sum = 0
            this.orders.forEach(order => {
                sum += order.sum_product
            })
            return sum
        },
        countBonusInOrder() {
            let sum = 0
            let bonus = 0
            this.orders.forEach(order => {
                sum += order.sum_product
                if (order.sum_product >= 20) {
                    bonus += 4
                } else if (order.sum_product >= 10) {
                    bonus += 2
                }
            })
            return sum + bonus
        },
        countBonus() {
            return this.countBonusInOrder - this.sumTodayProducts
        },
    },
    methods: {
        getOrders() {
            this.loading = true
            return axios.get('/api/today-admin-orders')
                .then((response) => {
                    this.loading = false
                    this.orders = response.data.data
                })
        },
        getProducts() {
            return axios.get('/api/today-admin-products')
                .then((response) => {
                    this.products = response.data.data
                })
        },
        deleteOrder(order) {
            if (confirm('Are you sure?')) {
                this.$axios.get('/sanctum/csrf-cookie').then(response => {
                    console.log(response)
                    axios
                        .delete('/api/admin-remove-order/' + order.id, {
                                headers: response.config.headers,
                            }
                        ).then(response => {
                        this.getOrders()
                        this.getProducts()
                    })
                })
            }
        },
        countBonusRolls(sum) {
            let bonus;
            if (sum >= 10) {
                bonus = 2
            }
            if (sum >= 20) {
                bonus = 4
            }
            return bonus;
        },
    },
    mounted() {
        this.getOrders()
        this.getProducts()
        if (window.Laravel.isLoggedin) {
            this.auth = true
        }
    }
}
</script>

<style scoped>

</style>
