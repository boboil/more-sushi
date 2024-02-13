<template>
    <div class="col-12 mb-5">
        <h2>Ролы</h2>
        <table class="table">
            <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Название</th>
                <th scope="col">Порядок сортировки</th>
                <th scope="col">Удалить</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(product, key) in products">
                <th scope="row">{{ key + 1 }}</th>
                <td>{{ product.title }}</td>
                <td>{{ product.sort_order }}</td>
                <td>
                    <button type="button" class="btn btn-danger" @click="deleteProduct(product)">Удалить</button>
                    <button type="button" class="btn btn-info" @click="showUpdateModal(product)">Редактировать</button>
                </td>
            </tr>
            </tbody>
        </table>
        <div class="row">
            <div class="col-xs-2">
                <button type="button" class="btn btn-primary" @click="showAddModal">
                    Добавить рол
                </button>
            </div>
        </div>
        <b-modal title="Добавить новый ролл" v-model="showModal">
            <div class="modal-body">
                <div class="mb-3">
                    <label for="recipient-name" class="col-form-label">Название:</label>
                    <input
                        v-model="addedProduct.title"
                        type="text"
                        class="form-control"
                    >
                </div>
                <div class="mb-3">
                    <label for="recipient-name" class="col-form-label">Порядок сортировки:</label>
                    <input
                        v-model="addedProduct.sort_order"
                        type="number"
                        class="form-control"
                    >
                </div>
            </div>
            <template #modal-footer>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" v-on:click="addProduct">
                    {{ modalFooterSubmitTitle }}
                </button>
                <b-button
                    variant="outline"
                    size="sm"
                    @click="showModal=false"
                >
                    Закрыть
                </b-button>
            </template>
        </b-modal>
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
            addedProduct: {
                id: null,
                title: '',
                sort_order: 0
            },
            showModal: false,
            modalFooterSubmitTitle: 'Создать',
            action: '/api/admin-create-product'
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
                })
        },
        deleteProduct(product) {
            if (confirm('Are you sure?')) {
                this.$axios.get('/sanctum/csrf-cookie').then(response => {
                    axios.delete('/api/admin-remove-product/' + product.id, {
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
            return axios.post(`${this.action}`, this.addedProduct)
                .then((response) => {
                    this.loading = false;
                    this.addedProduct = {}
                    this.showThanksModal('Товар добавлен!')
                    this.showModal = !this.showModal
                }).then(() => {
                    this.getProducts()
                })
        },
        showUpdateModal(product) {
            this.addedProduct = product
            this.action = 'api/admin-update-product'
            this.modalFooterSubmitTitle = 'Редактировать'
            this.showModal = !this.showModal
        },
        showAddModal() {
            this.addedProduct = {}
            this.action = '/api/admin-create-product'
            this.modalFooterSubmitTitle = 'Создать'
            this.showModal = !this.showModal
        },
        showThanksModal(text) {
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
