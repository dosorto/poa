<x-elegant-delete-modal 
    wire:model="showDeleteModal"
    title="Confirmar Eliminación"
    message="¿Estás seguro de que deseas eliminar este CUB?"
    :entity="$cubToDelete"
    confirm-method="delete"
    cancel-method="closeDeleteModal"
    confirm-text="Eliminar CUB"
    cancel-text="Cancelar"
/>