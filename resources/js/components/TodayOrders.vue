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
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="order in orders">
                        <th scope="row">{{ order.id }}</th>
                        <td>{{ order.address }}</td>
                        <td>
                <span v-for="product in order.products.data">
                    {{ product.title }} - {{ product.quantity }} шт. <br>
                </span>
                        </td>
                        <td>{{ order.sum_product >= 10 ? '2 шт' : '-' }}</td>
                        <td>{{ order.order_time }}</td>
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
                if(order.sum_product >= 10)
                    bonus += 2

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
                    console.log(this.products)
                })
        }
    },
    mounted() {
        this.getOrders()
        this.getProducts()
    }
}
</script>

<style scoped>

</style>
