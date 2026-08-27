{{-- Wadah toast global. Diisi lewat Alpine store "toasts" (resources/js/app.js). --}}
<div class="toast-host" x-data aria-live="polite" aria-atomic="true">
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div class="eams-toast" :class="'is-' + toast.type" role="alert" x-transition.duration.250ms>
            <span class="eams-toast__icon"><i class="bi" :class="toast.icon"></i></span>

            <div class="eams-toast__body">
                <p class="eams-toast__title" x-text="toast.title"></p>
                <p class="eams-toast__text" x-text="toast.message"></p>
            </div>

            <button type="button" class="eams-toast__close" @click="$store.toasts.remove(toast.id)" aria-label="Tutup notifikasi">
                <i class="bi bi-x-lg"></i>
            </button>

            <span class="eams-toast__progress"
                  x-show="toast.timeout > 0"
                  :style="'animation-duration: ' + toast.timeout + 'ms'"></span>
        </div>
    </template>
</div>
