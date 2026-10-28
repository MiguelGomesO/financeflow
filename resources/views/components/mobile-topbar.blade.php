<header class="sticky top-0 z-20 flex items-center justify-between border-b border-line bg-canvas/95 px-4 py-3 backdrop-blur lg:hidden">
    <x-logo tone="dark" />
    <button
        type="button"
        class="flex size-11 items-center justify-center rounded-xl border border-line-strong bg-surface text-strong"
        x-on:click="sidebarOpen = true"
        aria-label="Abrir menu"
        aria-controls="sidebar"
        x-bind:aria-expanded="sidebarOpen"
    >
        <x-icon name="menu" />
    </button>
</header>
