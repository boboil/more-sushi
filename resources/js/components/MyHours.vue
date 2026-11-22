<template>
    <main class="container my-hours">
        <div v-if="!isLoggedIn" class="alert alert-info">
            Щоб вносити години, <router-link to="/login">увійдіть</router-link> під своїм обліковим записом.
        </div>

        <template v-else>
            <div class="mh-head">
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="shift(-1)" aria-label="Попередній місяць">‹</button>
                <h4 class="mb-0">{{ monthLabel }}</h4>
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="shift(1)" aria-label="Наступний місяць">›</button>
            </div>

            <p class="mh-total">
                {{ data ? data.name : '' }} · за місяць: <b>{{ format(data ? data.totalHours : 0) }} год</b>
            </p>

            <div v-if="error" class="alert alert-danger">{{ error }}</div>

            <div class="mh-grid" v-if="data">
                <div class="mh-dow" v-for="d in weekdays" :key="d">{{ d }}</div>
                <div v-for="n in leadingBlanks" :key="'b' + n" class="mh-blank"></div>
                <button
                    v-for="day in data.days"
                    :key="day.date"
                    type="button"
                    class="mh-day"
                    :class="{ today: day.date === data.today, filled: day.hours, admin: day.hours && day.source !== 'self', locked: !day.editable }"
                    :disabled="!day.editable"
                    @click="open(day)"
                >
                    <span class="mh-num">{{ day.day }}</span>
                    <span class="mh-hours">{{ day.hours ? format(day.hours) : '' }}</span>
                </button>
            </div>

            <p class="mh-note">
                Можна вносити години за сьогодні і за попередні дні, починаючи з {{ data && data.editFrom ? dateLabel(data.editFrom) : 'останніх 7 днів' }}.
                <span class="mh-legend admin"></span> — години вніс адміністратор, змінити їх може лише він.
            </p>

            <b-modal
                id="mh-edit"
                v-model="modalOpen"
                :title="editing ? 'Години за ' + dateLabel(editing.date) : ''"
                ok-title="Зберегти"
                cancel-title="Скасувати"
                :ok-disabled="saving"
                @ok.prevent="save"
                centered
            >
                <label for="mh-input" class="form-label">Скільки годин відпрацювали</label>
                <input
                    id="mh-input"
                    ref="input"
                    v-model="hours"
                    type="number"
                    inputmode="decimal"
                    min="0"
                    max="24"
                    step="0.5"
                    class="form-control form-control-lg"
                    @keyup.enter="save"
                >
                <button v-if="editing && editing.hours" type="button" class="btn btn-link text-danger px-0 mt-2" @click="clear">
                    Не працював(-ла) — прибрати години
                </button>
            </b-modal>
        </template>
    </main>
</template>

<script>
const MONTHS = ['січень', 'лютий', 'березень', 'квітень', 'травень', 'червень', 'липень', 'серпень', 'вересень', 'жовтень', 'листопад', 'грудень']
const MONTHS_GEN = ['січня', 'лютого', 'березня', 'квітня', 'травня', 'червня', 'липня', 'серпня', 'вересня', 'жовтня', 'листопада', 'грудня']

export default {
    name: 'MyHours',
    data() {
        const now = new Date()
        return {
            isLoggedIn: !!(window.Laravel && window.Laravel.isLoggedin),
            month: now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'),
            data: null,
            error: null,
            weekdays: ['пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'нд'],
            modalOpen: false,
            editing: null,
            hours: '',
            saving: false,
        }
    },
    computed: {
        monthLabel() {
            const [y, m] = this.month.split('-').map(Number)
            const name = MONTHS[m - 1]
            return name.charAt(0).toUpperCase() + name.slice(1) + ' ' + y
        },
        leadingBlanks() {
            return this.data && this.data.days.length ? this.data.days[0].weekday - 1 : 0
        },
    },
    created() {
        if (this.isLoggedIn) {
            this.load()
        }
    },
    methods: {
        headers() {
            const meta = document.querySelector('meta[name="csrf-token"]')
            return { 'X-CSRF-TOKEN': meta ? meta.content : '', Accept: 'application/json' }
        },
        load() {
            this.error = null
            return this.$axios.get('/staff/hours', { params: { month: this.month }, headers: this.headers() })
                .then(r => { this.data = r.data })
                .catch(e => { this.error = this.message(e) })
        },
        shift(delta) {
            const [y, m] = this.month.split('-').map(Number)
            const d = new Date(y, m - 1 + delta, 1)
            this.month = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0')
            this.load()
        },
        open(day) {
            if (!day.editable) return
            this.editing = day
            this.hours = day.hours || ''
            this.modalOpen = true
            this.$nextTick(() => setTimeout(() => this.$refs.input && this.$refs.input.focus(), 150))
        },
        submit(hours) {
            this.saving = true
            this.error = null
            return this.$axios.post('/staff/hours', { date: this.editing.date, hours }, { headers: this.headers() })
                .then(r => {
                    this.data = r.data
                    this.modalOpen = false
                })
                .catch(e => {
                    this.modalOpen = false
                    this.error = this.message(e)
                })
                .finally(() => { this.saving = false })
        },
        save() {
            const value = String(this.hours).replace(',', '.')
            this.submit(value === '' ? null : Number(value))
        },
        clear() {
            this.submit(null)
        },
        message(e) {
            if (e.response && e.response.status === 401) {
                return 'Сесія закінчилась — увійдіть ще раз.'
            }
            if (e.response && e.response.data && e.response.data.message) {
                return e.response.data.message
            }
            return 'Не вдалося зберегти. Спробуйте ще раз.'
        },
        format(v) {
            return Number(v || 0).toLocaleString('uk-UA', { maximumFractionDigits: 2 })
        },
        dateLabel(date) {
            const [, m, d] = date.split('-').map(Number)
            return d + ' ' + MONTHS_GEN[m - 1]
        },
    },
}
</script>

<style scoped>
.my-hours { max-width: 560px; }
.mh-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .5rem; }
.mh-total { color: #555; }
.mh-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
.mh-dow { text-align: center; font-size: .75rem; color: #888; padding-bottom: 2px; }
.mh-day {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    min-height: 56px; border: 1px solid #dee2e6; border-radius: 8px; background: #fff; padding: 4px 0;
}
.mh-day:not(:disabled):hover { border-color: #f59e0b; }
.mh-day.today { border: 2px solid #f59e0b; }
.mh-day.filled { background: #fff7e6; }
.mh-day.admin { background: #e8f1ff; }
.mh-day.locked:not(.filled) { opacity: .45; }
.mh-day.locked { cursor: default; color: inherit; }
.mh-num { font-size: .75rem; color: #666; }
.mh-hours { font-weight: 700; font-size: 1rem; min-height: 1.25rem; }
.mh-note { font-size: .8125rem; color: #777; margin-top: .75rem; }
.mh-legend { display: inline-block; width: .8rem; height: .8rem; border-radius: 3px; vertical-align: middle; }
.mh-legend.admin { background: #e8f1ff; border: 1px solid #b6cff5; }
</style>
