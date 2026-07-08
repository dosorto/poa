<x-elegant-delete-modal
    wire:model="showDeleteModal"
    title="Confirmar Eliminación"
    message="¿Estás seguro de que deseas eliminar este objeto de gasto?"
    :entity="$objetoGastoToDelete"
    confirm-method="delete"
    cancel-method="closeDeleteModal"
    confirm-text="Eliminar Objeto"
    cancel-text="Cancelar"
/>
