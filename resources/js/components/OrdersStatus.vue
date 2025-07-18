<template>
    <div class="container mt-4">
        <h2>Текущие заказы</h2>
        <div class="form-group mb-4">
            <label for="establishmentSelect">Выберите заведение</label>
            <select v-model="selectedEstablishment" @change="fetchData" class="form-control" id="establishmentSelect">
                <option v-for="est in establishments" :key="est.id" :value="est.id">
                    {{ est.name }}
                </option>
            </select>
        </div>
        <div v-for="order in orders" :key="order.transaction_id" class="card mb-4">
            <div class="card-header">
                Заказ № {{ order.transaction_id }}
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tbody>
                    <tr v-for="(value, key) in order" :key="key" v-if="key !== 'products' && key !== 'transaction_id'">
                        <th>{{ key }}</th>
                        <td>{{ value }}</td>
                    </tr>
                    </tbody>
                </table>
                <div v-if="order.products && order.products.length" class="mt-3">
                    <h4>Содержимое заказа</h4>
                    <table class="table table-sm table-striped">
                        <thead>
                        <tr>
                            <th v-for="(pVal, pKey) in order.products[0]" :key="pKey">
                                {{ pKey }}
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(product, index) in order.products" :key="index">
                            <td v-for="(pVal, pKey) in product" :key="pKey">
                                {{ pVal }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "OrdersStatus",
    data() {
        return {
            orders: [],
            loading: false,
            establishments: [
                {id: 0, name: 'Все заведения'},
                {id: 4, name: 'Троицкая'},
                {id: 5, name: 'Проспект Шевченко'},
                {id: 6, name: 'Курская'},
            ],
            selectedEstablishment: {id: 0, name: 'Все заведения'}
        };
    },
    methods: {
        async fetchData() {
            this.loading = true;
            try {
                const response = await axios.get('/api/get-status-orders',{
                    params: { establishment: this.selectedEstablishment }
                })
                this.orders = response.data
            } catch (error) {
                console.error(error);
            } finally {
                this.loading = false;
            }
        }
    },
    mounted() {
        this.fetchData();
        setInterval(this.fetchData, 60000);
    }
};
</script>
