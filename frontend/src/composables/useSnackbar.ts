import { ref } from 'vue';

const snackbarVisible = ref(false);
const snackbarMessage = ref('');
const snackbarColor = ref('');

export function useSnackbar() {
  const showSnackbar = (param: string, variant: 'success' | 'warning' | 'error') => {
    let message = '';
    let color = '';

    if (variant === 'success') {
      message = `Opération effectuée avec succès`;
      color = 'success';
    } else if (variant === 'warning') {
      message = `L'action concernant ${param} a été réalisée avec avertissement`;
      color = 'warning';
    } else if (variant === 'error') {
      message = `Error : ${param}`;
      color = 'error';
    }

    snackbarMessage.value = message;
    snackbarColor.value = color;
    snackbarVisible.value = true;
  };

  const closeSnackbar = () => {
    snackbarVisible.value = false;
  };

  return {
    snackbarVisible,
    snackbarMessage,
    snackbarColor,
    showSnackbar,
    closeSnackbar,
  };
}
