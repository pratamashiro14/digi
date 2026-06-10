(function () {
    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[data-swal-confirm]');
        if (!link || typeof swal !== 'function') {
            return;
        }

        event.preventDefault();
        var message = link.getAttribute('data-swal-confirm') || 'Data yang dihapus tidak dapat dikembalikan.';

        swal({
            title: 'Yakin ingin menghapus?',
            text: message,
            icon: 'warning',
            buttons: ['Batal', 'Ya, Hapus'],
            dangerMode: true
        }).then(function (confirmed) {
            if (confirmed) {
                window.location.href = link.href;
            }
        });
    });
})();
