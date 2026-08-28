{{-- Wadah toast global. Diisi lewat Alpine store "toasts" (resources/js/app.js). --}}
<div class="toast-host" x-data aria-live="polite" aria-atomic="true">
    <template x-for="toast in $store.toasts.items" x-bind:key="toast.id">
        <div
            class="eams-toast"
            x-bind:class="{
                'is-success': toast.type === 'success',
                'is-error': toast.type === 'error',
                'is-warning': toast.type === 'warning',
                'is-info': toast.type === 'info'
            }"
            role="alert"
        >
            <span class="eams-toast__icon"><i class="bi" x-bind:class="toast.icon"></i></span>

            <div class="eams-toast__body">
                <p class="eams-toast__title" x-text="toast.title"></p>
                <p class="eams-toast__text" x-text="toast.message"></p>
            </div>

            <button type="button" class="eams-toast__close" x-on:click="$store.toasts.remove(toast.id)" aria-label="Tutup notifikasi">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </template>
</div>
