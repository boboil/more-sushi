<template>

</template>
<script>
export default {
    name: "SalaryPage",
    data() {
        return {
            users: [],
            loading: false,
            products: [],
            auth: false,
        }
    },
    computed: {

    },
    methods: {
        getUsers() {
            this.loading = true
            return axios.get('/api/get-users')
                .then((response) => {
                    this.loading = false
                    this.users = response.data.data
                })
        },
    },
    mounted() {
        if (window.Laravel.isLoggedin) {
            this.auth = true
        }
        if (this.auth === false) {
            location.href = '/'
        }
    }
}
</script>
<script setup lang="ts">
</script>
