/**
 * Tooltip do projeto. Troca a dica nativa do navegador (atributo `title`) por
 * um balão no padrão visual do app. Também aceita `data-tooltip="texto"`.
 *
 * Enquanto o balão aparece, o `title` é guardado e removido (senão o navegador
 * mostraria a dica dele por cima); ao sair, volta ao elemento, então o nome
 * acessível do botão não se perde. Itens do menu lateral mostram o balão à direita.
 */
const DELAY = 250;
const GAP = 10;
const EDGE = 8;

let tip = null;
let label = null;
let arrow = null;
let current = null;
let savedTitle = null;
let timer = null;

const canHover = () => window.matchMedia('(hover: hover)').matches;

function ensureTip() {
    if (tip?.isConnected) {
        return;
    }

    tip = document.createElement('div');
    tip.setAttribute('role', 'tooltip');
    tip.className = 'pointer-events-none fixed top-0 left-0 z-[70] max-w-xs rounded-lg bg-ink px-2.5 py-1.5 text-xs leading-snug font-medium text-white opacity-0 shadow-lg transition-opacity duration-150';

    label = document.createElement('span');
    arrow = document.createElement('span');
    arrow.className = 'absolute size-2 rotate-45 bg-ink';

    tip.append(label, arrow);
    document.body.append(tip);
}

function place(el) {
    const rect = el.getBoundingClientRect();
    const box = tip.getBoundingClientRect();
    const { innerWidth: vw, innerHeight: vh } = window;

    let side = el.closest('#sidebar') ? 'right' : 'top';

    if (side === 'top' && rect.top - box.height - GAP < EDGE) {
        side = 'bottom';
    }

    if (side === 'right' && rect.right + box.width + GAP > vw - EDGE) {
        side = 'top';
    }

    let x;
    let y;

    if (side === 'right') {
        x = rect.right + GAP;
        y = rect.top + rect.height / 2 - box.height / 2;
    } else {
        x = rect.left + rect.width / 2 - box.width / 2;
        y = side === 'top' ? rect.top - box.height - GAP : rect.bottom + GAP;
    }

    x = Math.min(Math.max(x, EDGE), Math.max(EDGE, vw - box.width - EDGE));
    y = Math.min(Math.max(y, EDGE), Math.max(EDGE, vh - box.height - EDGE));

    tip.style.transform = `translate(${Math.round(x)}px, ${Math.round(y)}px)`;

    // A setinha aponta para o centro do elemento, mesmo quando o balão foi empurrado para dentro da tela.
    const centerX = Math.min(Math.max(rect.left + rect.width / 2 - x - 4, 8), box.width - 16);
    const centerY = Math.min(Math.max(rect.top + rect.height / 2 - y - 4, 8), box.height - 16);

    arrow.style.cssText = {
        top: `bottom:-4px;left:${centerX}px`,
        bottom: `top:-4px;left:${centerX}px`,
        right: `left:-4px;top:${centerY}px`,
    }[side];
}

function show(el, text) {
    ensureTip();
    label.textContent = text;
    place(el);
    tip.style.opacity = '1';
}

function hide() {
    clearTimeout(timer);
    timer = null;

    if (tip) {
        tip.style.opacity = '0';
    }

    if (current && savedTitle !== null && !current.hasAttribute('title')) {
        current.setAttribute('title', savedTitle);
    }

    current = null;
    savedTitle = null;
}

function enter(event) {
    if (!canHover() || (current && current.contains(event.target))) {
        return;
    }

    const el = event.target.closest?.('[title], [data-tooltip]');
    const text = el?.getAttribute('title') || el?.dataset.tooltip;

    if (!el || !text) {
        return;
    }

    hide();
    current = el;

    if (el.hasAttribute('title')) {
        savedTitle = el.getAttribute('title');
        el.removeAttribute('title');
    }

    timer = setTimeout(() => show(el, text), DELAY);
}

function leave(event) {
    if (current && !current.contains(event.relatedTarget)) {
        hide();
    }
}

document.addEventListener('mouseover', enter);
document.addEventListener('mouseout', leave);
document.addEventListener('focusin', enter);
document.addEventListener('focusout', leave);
document.addEventListener('keydown', (event) => event.key === 'Escape' && hide());
document.addEventListener('click', () => hide(), true);
document.addEventListener('scroll', () => hide(), true);
document.addEventListener('livewire:navigate', () => hide());
