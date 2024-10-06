<template>
    <div class="col-12 mb-5">
        <h2>Ролы</h2>
        <table class="table">
            <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Название</th>
                <th scope="col">Удалить</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(product, key) in products">
                <th scope="row">{{ key + 1 }}</th>
                <td>{{ product.title }}</td>
                <td>
                    <button type="button" class="btn btn-danger" @click="deleteProduct(product)">Удалить</button>
                </td>
            </tr>
            </tbody>
        </table>
        <div class="row">
            <div class="col-xs-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    Добавить рол
                </button>
            </div>
        </div>

        <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModal" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addModalLabel">Добавить новый ролл</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="recipient-name" class="col-form-label">Название:</label>
                            <input
                                v-model="product.name"
                                type="text"
                                class="form-control"
                                name="rollTitle"
                                id="roll-title"
                            >
                        </div>
                        <div class="mb-3">
                            <label for="recipient-name" class="col-form-label">Порядок сортировки:</label>
                            <input
                                v-model="product.sort_order"
                                type="number"
                                class="form-control"
                            >
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal" v-on:click="addProduct">
                            Создать
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "ProductsTable",
    data() {
        return {
            products: [],
            isLoggedIn: false,
            loading: false,
            addedProduct: '',
            product: {
                id: 0,
                sort_order: 0,
                name: ''
            }
        }
    },
    mounted() {
        if (window.Laravel.isLoggedin) {
            this.isLoggedIn = true
        }
        this.getProducts()
    },
    methods: {
        getProducts() {
            this.loading = true
            return axios.get('/api/products')
                .then((response) => {
                    this.loading = false;
                    this.products = response.data.data
                    console.log(this.products)
                })
        },
        deleteProduct(product) {
            if (confirm('Are you sure?')) {
                this.$axios.get('/sanctum/csrf-cookie').then(response => {
                    console.log(response)
                    axios
                        .delete('/api/admin-remove-product/' + product.id, {
                                headers: response.config.headers,
                            }
                        ).then(response => {
                        this.showModal('Товар удалён!')
                        this.getProducts()
                    })
                })
            }
        },
        addProduct() {
            this.loading = true;
            return axios.post('/api/admin-create-product', this.product)
                .then((response) => {
                    this.loading = false;

                    this.product = {}
                    this.showModal('Товар добавлен!')
                }).then(() => {
                    this.getProducts()
                })
        },
        updateProduct() {
            this.loading = true;
            return axios.post('/api/admin-update-product', this.product)
                .then((response) => {
                    this.loading = false;

                    this.product = {}
                    this.showModal('Товар обновлен!')
                }).then(() => {
                    this.getProducts()
                })
        },
        showModal(text) {
            Swal.fire({
                icon: 'success',
                title: 'Спасибо',
                text: text,
            })
        },
    }
}
</script>

<style scoped>

</style>
