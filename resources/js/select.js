/**
 * Select do projeto (substitui a lista nativa do navegador, que não aceita estilo).
 *
 * As opções vêm de um <select hidden> renderizado pelo Blade (x-ui.select); o valor
 * escolhido vai e volta do Livewire por x-modelable, como no calendário. A lista
 * abre com posição fixa, para não ser cortada por modais e áreas com rolagem.
 */
const MAX_HEIGHT = 288;
const GAP = 6;
const EDGE = 8;

export default () => ({
    value: '',
    open: false,
    active: -1,
    groups: [],
    listStyle: '',
    typed: '',
    typedTimer: null,
    observer: null,

    init() {
        this.read();

        // O Livewire troca as opções quando os dados mudam (ex.: categorias por tipo).
        this.observer = new MutationObserver(() => this.read());
        this.observer.observe(this.$refs.source, {
            childList: true,
            subtree: true,
            characterData: true,
            attributes: true,
            attributeFilter: ['disabled', 'label', 'value'],
        });
    },

    destroy() {
        this.observer?.disconnect();
    },

    read() {
        let index = 0;
        const item = (option) => ({ value: option.value, text: option.textContent.trim(), disabled: option.disabled, index: index++ });

        this.groups = [...this.$refs.source.children].flatMap((node) => {
            if (node.tagName === 'OPTION') {
                return [{ label: null, items: [item(node)] }];
            }

            if (node.tagName === 'OPTGROUP') {
                return [{ label: node.label, items: [...node.children].map(item) }];
            }

            return [];
        });
    },

    get items() {
        return this.groups.flatMap((group) => group.items);
    },

    get label() {
        return this.items.find((option) => option.value === String(this.value ?? ''))?.text ?? '';
    },

    isSelected(option) {
        return option.value === String(this.value ?? '');
    },

    toggle() {
        this.open ? this.close() : this.show();
    },

    show() {
        if (this.$refs.trigger.disabled) {
            return;
        }

        const rect = this.$refs.trigger.getBoundingClientRect();
        const below = window.innerHeight - rect.bottom - EDGE - GAP;
        const above = rect.top - EDGE - GAP;
        const up = below < 180 && above > below;
        const height = Math.min(MAX_HEIGHT, up ? above : below);
        const width = Math.max(rect.width, 160);
        const left = Math.min(Math.max(rect.left, EDGE), Math.max(EDGE, window.innerWidth - width - EDGE));

        this.listStyle = `left:${left}px;width:${width}px;max-height:${height}px;`
            + (up ? `bottom:${window.innerHeight - rect.top + GAP}px` : `top:${rect.bottom + GAP}px`);

        this.active = this.items.findIndex((option) => this.isSelected(option) && !option.disabled);
        this.open = true;
        this.$nextTick(() => this.reveal());
    },

    close() {
        this.open = false;
    },

    choose(option) {
        if (option.disabled) {
            return;
        }

        this.value = option.value;
        this.close();
        this.$refs.trigger.focus();
    },

    move(step) {
        const items = this.items;

        for (let i = this.active + step; i >= 0 && i < items.length; i += step) {
            if (!items[i].disabled) {
                this.active = i;
                this.reveal();

                return;
            }
        }
    },

    /** Mantém a opção ativa à vista dentro da lista. */
    reveal() {
        this.$refs.list?.querySelector(`[data-index="${this.active}"]`)?.scrollIntoView({ block: 'nearest' });
    },

    onKey(event) {
        const { key } = event;

        if (!this.open) {
            if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(key)) {
                event.preventDefault();
                this.show();
            }

            return;
        }

        switch (key) {
            case 'ArrowDown':
                event.preventDefault();
                this.move(1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.move(-1);
                break;
            case 'Home':
            case 'End': {
                event.preventDefault();
                this.active = key === 'Home' ? -1 : this.items.length;
                this.move(key === 'Home' ? 1 : -1);
                break;
            }
            case 'Enter':
            case ' ':
                event.preventDefault();
                if (this.items[this.active]) {
                    this.choose(this.items[this.active]);
                }
                break;
            case 'Escape':
                // Só fecha a lista; sem isso o modal em volta também fecharia.
                event.stopPropagation();
                this.close();
                break;
            case 'Tab':
                this.close();
                break;
            default:
                if (key.length === 1 && !event.ctrlKey && !event.metaKey) {
                    this.typeahead(key.toLowerCase());
                }
        }
    },

    /** Digitar letras salta para a opção que começa com elas. */
    typeahead(char) {
        clearTimeout(this.typedTimer);
        this.typed += char;
        this.typedTimer = setTimeout(() => (this.typed = ''), 600);

        const found = this.items.find((option) => !option.disabled && option.text.toLowerCase().startsWith(this.typed));

        if (found) {
            this.active = found.index;
            this.reveal();
        }
    },

    onScroll(event) {
        if (this.open && !(event.target instanceof Element && event.target.closest('[data-select-list]'))) {
            this.close();
        }
    },
});
