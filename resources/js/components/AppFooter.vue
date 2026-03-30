<template>
    <footer class="border-t border-white/10 bg-black/20">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5 text-xs text-white/70">
            <div>© {{ year }} • Desenvolvido por: <span class="font-semibold text-[#e9c15e]">Roni Deringer</span></div>

            <div class="flex items-center gap-3">
                <a class="rounded-lg p-2 text-white/70 ring-1 ring-white/10 hover:bg-white/10 hover:text-white"
                    href="https://www.linkedin.com/in/ronideringer/" target="_blank" rel="noopener noreferrer"
                    aria-label="LinkedIn" title="LinkedIn">
                    <svg viewBox="0 0 24 24" class="size-5" fill="currentColor" aria-hidden="true">
                        <path
                            d="M20.447 20.452H17.21v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.0V9h3.112v1.561h.044c.433-.82 1.492-1.685 3.07-1.685 3.285 0 3.89 2.164 3.89 4.977v6.599zM5.337 7.433a1.81 1.81 0 1 1 0-3.62 1.81 1.81 0 0 1 0 3.62zM6.956 20.452H3.718V9H6.956v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.727v20.545C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.273V1.727C24 .774 23.2 0 22.222 0h.003z" />
                    </svg>
                </a>

                <a class="rounded-lg p-2 text-white/70 ring-1 ring-white/10 hover:bg-white/10 hover:text-white"
                    href="https://www.linkedin.com/in/ronideringer/" target="_blank" rel="noopener noreferrer"
                    aria-label="GitHub" title="GitHub">
                    <svg viewBox="0 0 24 24" class="size-5" fill="currentColor" aria-hidden="true">
                        <path
                            d="M12 .5a12 12 0 0 0-3.792 23.39c.6.11.82-.26.82-.58v-2.02c-3.338.73-4.042-1.61-4.042-1.61-.546-1.39-1.333-1.76-1.333-1.76-1.09-.75.083-.74.083-.74 1.205.09 1.84 1.24 1.84 1.24 1.07 1.83 2.807 1.3 3.492.99.11-.78.418-1.3.762-1.6-2.665-.3-5.466-1.33-5.466-5.93 0-1.31.47-2.38 1.235-3.22-.123-.3-.535-1.52.117-3.16 0 0 1.008-.32 3.3 1.23a11.5 11.5 0 0 1 6 0c2.29-1.55 3.297-1.23 3.297-1.23.653 1.64.24 2.86.118 3.16.77.84 1.233 1.91 1.233 3.22 0 4.61-2.804 5.62-5.476 5.92.43.37.81 1.1.81 2.22v3.29c0 .32.22.69.83.58A12 12 0 0 0 12 .5z" />
                    </svg>
                </a>
            </div>
        </div>
    </footer>
</template>

<script setup>
const year = new Date().getFullYear()
</script>
