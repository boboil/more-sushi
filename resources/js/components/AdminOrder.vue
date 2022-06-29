<template>
    <main class="container">
        <form id="app" class="order-form">
            <div class="input-group m-3 roll col-sm-10" v-for="(item, index) in selected">
                <div class="row">
                    <div class="col-xs-2">
                        <label>&nbsp;</label>
                        <button type="button" v-on:click="removeSelected(index)" class="btn btn-block btn-danger"
                                v-if="selected.length > 1">
                            Удалить -
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <select class="form-select" required v-model="item.id" name="selected[][title]">
                            <option selected>Выберете ролл</option>
                            <option v-for="option in options" :value="option.value">{{ option.text }}</option>
                        </select>
                        <input type="number" v-model="item.quantity" name="selected[][quantity]" class="form-control"
                               aria-label="Text input with dropdown button"
                               placeholder="колличество" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-2">
                    <button type="button" v-on:click="addNewSelected" class="btn btn-block btn-success">
                        Добавить +
                    </button>
                </div>
                <div class="col-xs-10">
                    Хотите добавить ролл?
                </div>
            </div>

            <div class="row">
                <div class="col-4">
                    <div class="m-3">
                        <label for="address" class="form-label">Адрес</label>
                        <input type="text" class="form-control" name="address" id="address"
                               placeholder="Введите адрес магазина" v-model="address"
                               required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-2">
                    <button class="btn btn-block btn-primary" v-on:click="sendData">
                        Заказать
                    </button>
                </div>
            </div>
        </form>
    </main>
</template>

<script>
export default {
    name: "AdminOrder",
    data() {
        return {
            products: [],
            loading: false,
            selected: [{
                id: '',
                quantity: 0
            }],
            options: [],
            address: '',
        }
    },
    methods: {
        getProducts() {
            this.loading = true
            return axios.get('/api/admin-order')
                .then((response) => {
                    this.loading = false;
                    this.products = response.data.data
                    this.options = this.products.map(product => {
                        return {value: product.id, text: product.title}
                    })
                })
        },
        addNewSelected() {
            this.selected.push({id: '', quantity: 0})
        },
        removeSelected(index) {
            this.selected.splice(index, 1);
        },
        sendData(event) {
            if (event) {
                event.preventDefault()
            }
            let data = {
                selected: this.selected,
                address: this.address
            }
            this.loading = true;
            return axios.post('/api/admin-create-order', data)
                .then((response) => {
                    this.loading = false;
                    this.selected = [{
                        id: 0,
                        quantity: 0
                    }];
                    this.address = '';
                })
        }

    },
    mounted() {
        this.getProducts()
    }
}
</script>

<style scoped>

</style>
