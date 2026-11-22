<template>
    <main class="container day-cash">
        <div v-if="!allowed" class="alert alert-info">Вносити касу може лише адміністратор.</div>

        <template v-else-if="data">
            <h4 class="mb-3">Каса</h4>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="saved" class="alert alert-success">Збережено</div>

            <form class="dc-today" @submit.prevent="save(today)">
                <label for="dc-today" class="form-label">Каса за сьогодні, {{ dateLabel(today.date) }}</label>
                <div class="input-group input-group-lg">
                    <input id="dc-today" v-model="today.value" type="number" inputmode="decimal" min="0" step="0.01" class="form-control" placeholder="0">
                    <span class="input-group-text">грн</span>
                    <button class="btn btn-primary" type="submit" :disabled="saving">Зберегти</button>
                </div>
            </form>

            <h6 class="mt-4 mb-2 text-muted">Попередні дні</h6>
            <form v-for="day in previous" :key="day.date" class="dc-row" @submit.prevent="save(day)">
                <span class="dc-date">{{ dateLabel(day.date) }}</span>
                <div class="input-group input-group-sm">
                    <input v-model="day.value" type="number" inputmode="decimal" min="0" step="0.01" class="form-control" placeholder="не внесено" :aria-label="'Каса за ' + dateLabel(day.date)">
                    <span class="input-group-text">грн</span>
                    <button class="btn btn-outline-primary" type="submit" :disabled="saving || !changed(day)">Зберегти</button>
                </div>
            </form>
            <p class="dc-note">Можна виправити касу за останні {{ data.editDays }} днів. Порожнє поле — каси немає.</p>
        </template>
    </main>
</template>

<script>
const MONTHS_GEN = ['січня', 'лютого', 'березня', 'квітня', 'травня', 'червня', 'липня', 'серпня', 'вересня', 'жовтня', 'листопада', 'грудня']
const WEEKDAYS = ['', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'нд']

export default {
    name: 'DayCash',
    data() {
        return {
            allowed: !!(window.Laravel && window.Laravel.canEnterCash),
            data: null,
            days: [],
            error: null,
            saved: false,
            saving: false,
        }
    },
    computed: {
        today() {
            return this.days[0]
        },
        previous() {
            return this.days.slice(1)
        },
    },
    created() {
        if (this.allowed) {
            this.$axios.get('/staff/cash', { headers: this.headers() }).then(r => this.apply(r.data)).catch(e => { this.error = this.message(e) })
        }
    },
    methods: {
        headers() {
            const meta = document.querySelector('meta[name="csrf-token"]')
            return { 'X-CSRF-TOKEN': meta ? meta.content : '', Accept: 'application/json' }
        },
        apply(data) {
            this.data = data
            this.days = data.days.map(d => ({ ...d, value: d.cash === null ? '' : d.cash }))
        },
        changed(day) {
            return String(day.value) !== String(day.cash === null ? '' : day.cash)
        },
        save(day) {
            const raw = String(day.value).replace(',', '.').trim()
            this.saving = true
            this.error = null
            this.saved = false
            this.$axios.post('/staff/cash', { date: day.date, cash: raw === '' ? null : Number(raw) }, { headers: this.headers() })
                .then(r => { this.apply(r.data); this.saved = true; setTimeout(() => { this.saved = false }, 2500) })
                .catch(e => { this.error = this.message(e) })
                .finally(() => { this.saving = false })
        },
        message(e) {
            if (e.response && e.response.status === 401) return 'Сесія закінчилась — увійдіть ще раз.'
            if (e.response && e.response.data && e.response.data.message) return e.response.data.message
            return 'Не вдалося зберегти. Спробуйте ще раз.'
        },
        dateLabel(date) {
            const d = new Date(date + 'T12:00:00')
            return d.getDate() + ' ' + MONTHS_GEN[d.getMonth()] + ', ' + WEEKDAYS[d.getDay() === 0 ? 7 : d.getDay()]
        },
    },
}
</script>

<style scoped>
.day-cash { max-width: 520px; }
.dc-today { padding: 1rem; border: 1px solid #f59e0b; border-radius: 10px; background: #fffbf2; }
.dc-row { display: flex; align-items: center; gap: .75rem; margin-bottom: .5rem; }
.dc-date { width: 8.5rem; flex-shrink: 0; font-size: .9rem; }
.dc-note { font-size: .8125rem; color: #777; margin-top: .75rem; }
</style>
