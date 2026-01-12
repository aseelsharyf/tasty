import { ref } from 'vue';

const isOpen = ref(false);

export function useCreatePost() {
    function openCreateModal() {
        isOpen.value = true;
    }

    function closeCreateModal() {
        isOpen.value = false;
    }

    return {
        isCreateModalOpen: isOpen,
        openCreateModal,
        closeCreateModal,
    };
}
