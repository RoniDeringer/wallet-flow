import Swal from 'sweetalert2'

const baseToast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 2000,
  timerProgressBar: true,
  background: '#021e3d',
  color: '#ffffff',
})

export function toastSuccess(title = 'Sucesso', text = '') {
  return baseToast.fire({
    icon: 'success',
    title,
    text,
    iconColor: '#e9c15e',
  })
}

export function toastError(title = 'Erro', text = '') {
  return baseToast.fire({
    icon: 'error',
    title,
    text,
    iconColor: '#f43f5e',
  })
}

