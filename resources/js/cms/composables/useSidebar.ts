import { ref } from 'vue';

// Global reactive state for sidebar
const isHidden = ref(false);

export function useSidebar() {
    function hide() {
        isHidden.value = true;
    }

    function show() {
        isHidden.value = false;
    }

    function toggle() {
        isHidden.value = !isHidden.value;
    }

    return {
        isHidden,
        hide,
        show,
        toggle,
    };
}
