/*
 * Tema claro/escuro. A escolha ("light", "dark" ou "system") fica neste navegador; "system" segue o
 * sistema operacional. A classe "dark" no <html> é posta por um script no <head> antes da primeira
 * pintura (veja os layouts); aqui fica só o que muda depois da página carregar.
 */
const KEY = 'theme';
const MODES = ['system', 'light', 'dark'];
const query = window.matchMedia('(prefers-color-scheme: dark)');

export function storedMode() {
    try {
        const mode = localStorage.getItem(KEY);

        return MODES.includes(mode) ? mode : 'system';
    } catch (e) {
        return 'system';
    }
}

function isDark(mode) {
    return mode === 'dark' || (mode === 'system' && query.matches);
}

export function applyTheme(mode) {
    document.documentElement.classList.toggle('dark', isDark(mode));
}

export function setTheme(mode) {
    if (!MODES.includes(mode)) {
        return;
    }

    try {
        mode === 'system' ? localStorage.removeItem(KEY) : localStorage.setItem(KEY, mode);
    } catch (e) {}

    applyTheme(mode);
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: mode }));
}

// No modo "system", acompanha a troca do tema do sistema com a página aberta.
query.addEventListener('change', () => {
    if (storedMode() === 'system') {
        applyTheme('system');
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: 'system' }));
    }
});

/** Componente Alpine: x-data="themeSwitcher". */
export default function themeSwitcher() {
    return {
        mode: storedMode(),
        dark: document.documentElement.classList.contains('dark'),

        init() {
            window.addEventListener('theme-changed', (event) => {
                this.mode = event.detail;
                this.dark = document.documentElement.classList.contains('dark');
            });
        },

        set(mode) {
            setTheme(mode);
        },

        /** Atalho do menu: alterna entre claro e escuro, saindo do "automático". */
        toggle() {
            setTheme(this.dark ? 'light' : 'dark');
        },
    };
}
