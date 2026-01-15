<script>
function showSuccessAlert(title = 'Berhasil', text = 'Data berhasil diperbarui') {
    Swal.fire({
        icon: 'success',
        title: title,
        text: text,
        confirmButtonText: 'OK'
    });
}

function showErrorAlert(title = 'Gagal', text = 'Data gagal diperbarui') {
    Swal.fire({
        icon: 'error',
        title: title,
        text: text,
        confirmButtonText: 'OK'
    });
}
</script>
