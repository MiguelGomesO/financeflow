const MONTHS = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
];

const pad = (n) => String(n).padStart(2, '0');

const todayIso = () => {
    const d = new Date();

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
};

/**
 * Calendário do projeto (substitui o seletor nativo do navegador).
 * mode "date": valor "Y-m-d". mode "month": valor "Y-m".
 */
export default (mode = 'date') => ({
    value: '',
    open: false,
    up: false,
    year: new Date().getFullYear(),
    month: new Date().getMonth(),

    init() {
        this.syncView();
        this.$watch('value', () => this.syncView());
    },

    syncView() {
        const [year, month] = (this.value || todayIso()).split('-').map(Number);

        this.year = year;
        this.month = month - 1;
    },

    toggle() {
        if (!this.open) {
            this.syncView();

            // Abre para cima quando falta espaço embaixo.
            const rect = this.$refs.trigger.getBoundingClientRect();
            this.up = window.innerHeight - rect.bottom < 360 && rect.top > 360;
        }

        this.open = !this.open;
    },

    get label() {
        if (!this.value) {
            return mode === 'month' ? 'Selecione o mês' : 'dd/mm/aaaa';
        }

        const [year, month, day] = this.value.split('-');

        return mode === 'month' ? `${MONTHS[month - 1]} de ${year}` : `${day}/${month}/${year}`;
    },

    get title() {
        return mode === 'month' ? String(this.year) : `${MONTHS[this.month]} ${this.year}`;
    },

    get months() {
        return MONTHS.map((name, index) => ({ name: name.slice(0, 3), iso: `${this.year}-${pad(index + 1)}` }));
    },

    /** Dias do mês, com células vazias antes do dia 1 (semana começa no domingo). */
    get days() {
        const blanks = new Date(this.year, this.month, 1).getDay();
        const count = new Date(this.year, this.month + 1, 0).getDate();
        const cells = Array.from({ length: blanks }, () => null);

        for (let day = 1; day <= count; day++) {
            cells.push({ day, iso: `${this.year}-${pad(this.month + 1)}-${pad(day)}` });
        }

        return cells;
    },

    prev() {
        if (mode === 'month') {
            this.year--;

            return;
        }

        this.month === 0 ? ((this.month = 11), this.year--) : this.month--;
    },

    next() {
        if (mode === 'month') {
            this.year++;

            return;
        }

        this.month === 11 ? ((this.month = 0), this.year++) : this.month++;
    },

    pick(iso) {
        this.value = iso;
        this.open = false;
    },

    pickToday() {
        this.pick(mode === 'month' ? todayIso().slice(0, 7) : todayIso());
    },

    isToday(iso) {
        return todayIso().startsWith(iso) && (mode === 'month' || iso === todayIso());
    },
});
