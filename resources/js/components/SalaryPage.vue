<template>
    <div class="container mt-4">
        <div class="mb-3 d-flex justify-content-between">
            <div>
                <input
                    type="month"
                    class="form-control w-auto"
                    v-model="selectedMonth"
                    @change="fetchData"
                />
            </div>
            <div>
                <button
                    class="btn btn-primary btn-md"
                    :disabled="loading"
                    @click="fetchWorkingHours"
                >
                    Обновить часы сотрудников за текущий месяц
                </button>
            </div>
        </div>
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Имя сотрудника</th>
                <th>Должность</th>
                <th>Количество часов</th>
                <th>Заработано</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <template v-for="employee in employees">
                <tr>
                    <td>{{ employee.name }}</td>
                    <td>{{ employee.position }}</td>
                    <td>{{ employee.totalHours }}</td>
                    <td>{{ employee.totalEarnings }}</td>
                    <td>
                        <button
                            class="btn btn-primary btn-sm"
                            @click="toggleDetails(employee.id)"
                        >
                            {{ employee.showDetails ? 'Скрыть' : 'Подробнее' }}
                        </button>
                    </td>
                </tr>
                <tr v-if="employee.showDetails">
                    <td colspan="5">
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Ставка</th>
                                <th>Количество часов</th>
                                <th>Бонус</th>
                                <th>Заработано</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="day in employee.dailyDetails" :key="day.date">
                                <td>{{ day.date }}</td>
                                <td>{{ day.rate }}</td>
                                <td>{{ day.hours }}</td>
                                <td>{{ day.bonus }}</td>
                                <td>{{ day.earnings }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </template>
            </tbody>
        </table>
    </div>
</template>

<script>
export default {
    data() {
        return {
            loading: false,
            selectedMonth: new Date().toISOString().substr(0, 7),
            employees: [],
        };
    },
    methods: {
        async fetchData() {
            this.loading = true
            const response = await axios.post('/api/get-employees-working-hours', { month: this.selectedMonth })
            this.employees = await response.data
            this.loading = false
        },
        async fetchWorkingHours() {
            this.loading = true
            await axios.post('/api/get-working-hours-by-day', { month: this.selectedMonth })
            await this.fetchData()
            await this.showModal()
            this.loading = false
        },
        toggleDetails(employeeId) {
            const employee = this.employees.find((emp) => emp.id === employeeId);
            employee.showDetails = !employee.showDetails;
        },
        async showModal() {
            Swal.fire({
                icon: 'success',
                title: 'Готово',
                text: 'Все работчие часы за месяц обновлены',
            })
        },
    },
    mounted() {
        this.fetchData();
        if (!window.Laravel.isLoggedin) {
            window.location.href = '/'
        }
    },
};
</script>
