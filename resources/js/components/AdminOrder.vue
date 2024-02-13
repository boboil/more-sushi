<template>
    <main class="container">
        <form id="app" class="order-form" v-if="canEdit">
            <div v-if="errors.length">
                <b>Пожалуйста исправьте:</b>
                <ul>
                    <li v-for="error in errors">{{ error }}</li>
                </ul>
            </div>
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
                        <select class="form-select" required v-model="item.id" name="selected[][title]"
                                @change="selectOption($event)">
                            <option value="" disabled selected hidden>Выберете ролл</option>
                            <option v-for="option in options" :value="option.value" :disabled="option.disable">
                                {{ option.text }}
                            </option>
                        </select>
                        <input type="number" step="1" v-model="item.quantity" name="selected[][quantity]"
                               class="form-control"
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
                <div class="col-12 col-sm-4">
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
                    <button
                        v-on:click="sendData"
                        :disabled="!!loading"
                        class="btn btn-block btn-primary"
                    >
                        Заказать
                    </button>
                </div>
            </div>
        </form>
        <div v-if="!canEdit">
            <h1>Добавление заказов возможно с 12 до 24</h1>
            <span>Спасибо за понимание!</span>
        </div>
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
                id: null,
                quantity: null
            }],
            options: [],
            address: null,
            errors: []
        }
    },
    methods: {
        selectOption(event) {
            this.options.forEach(option => {
                if (parseInt(option.value) === parseInt(event.target.value)) {
                    option.disable = true
                }
            })
        },
        getProducts() {
            this.loading = true
            return axios.get('/api/admin-order')
                .then((response) => {
                    this.loading = false;
                    this.products = response.data.data
                    this.options = this.products.map(product => {
                        return {value: product.id, text: product.title, disable: false}
                    })
                })
        },
        addNewSelected() {
            this.selected.push({id: null, quantity: null})
        },
        removeSelected(index) {
            this.selected.splice(index, 1);
        },
        sendData(event) {
            this.errors = []
            this.loading = true
            if (event) {
                event.preventDefault()
            }
            this.selected.forEach(item => {
                if (!item.quantity || !item.id) {
                    this.errors.push('Заполните все поля формы');
                }
            })
            if (!this.address) {
                this.errors.push('Заполните все поля формы');
            }

            if (this.errors.length > 0) {
                this.loading = false
                return true;
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
                    this.showModal()
                    this.loading = false
                })
        },
        showModal() {
            Swal.fire({
                icon: 'success',
                title: 'Спасибо',
                text: 'Ваш заказ принят в обработку',
            })
        },

    },
    mounted() {
        this.getProducts()
    },
    computed: {
        canEdit() {
            const hours = new Date().getHours();
            // return hours > 12;
            return true
        }
    }
}
</script>

<style scoped>
/*--thank you pop starts here--*/
.thank-you-pop {
    width: 100%;
    padding: 20px;
    text-align: center;
}

.thank-you-pop img {
    width: 76px;
    height: auto;
    margin: 0 auto;
    display: block;
    margin-bottom: 25px;
}

.thank-you-pop h1 {
    font-size: 42px;
    margin-bottom: 25px;
    color: #5C5C5C;
}

.thank-you-pop p {
    font-size: 20px;
    margin-bottom: 27px;
    color: #5C5C5C;
}

.thank-you-pop h3.cupon-pop {
    font-size: 25px;
    margin-bottom: 40px;
    color: #222;
    display: inline-block;
    text-align: center;
    padding: 10px 20px;
    border: 2px dashed #222;
    clear: both;
    font-weight: normal;
}

.thank-you-pop h3.cupon-pop span {
    color: #03A9F4;
}

.thank-you-pop a {
    display: inline-block;
    margin: 0 auto;
    padding: 9px 20px;
    color: #fff;
    text-transform: uppercase;
    font-size: 14px;
    background-color: #8BC34A;
    border-radius: 17px;
}

.thank-you-pop a i {
    margin-right: 5px;
    color: #fff;
}

#ignismyModal .modal-header {
    border: 0;
}

/*--thank you pop ends here--*/

</style>
