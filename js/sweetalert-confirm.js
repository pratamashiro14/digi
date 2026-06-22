(function () {
    // Halaman admin tidak memuat css/main.css, jadi pertahankan warna
    // tombol pembatalan SweetAlert secara konsisten melalui style global ini.
    if (!document.getElementById('swal-cancel-danger-style')) {
        var style = document.createElement('style');
        style.id = 'swal-cancel-danger-style';
        style.textContent =
            '.swal-button--cancel{background-color:#dc2626!important;color:#fff!important;}' +
            '.swal-button--cancel:hover,.swal-button--cancel:focus{background-color:#b91c1c!important;}' +
            '.swal-button--cancel:active{background-color:#991b1b!important;}';
        document.head.appendChild(style);
    }

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[data-swal-confirm]');
        if (!link || typeof swal !== 'function') {
            return;
        }

        event.preventDefault();
        if (link.getAttribute('aria-disabled') === 'true') {
            return;
        }
        var message = link.getAttribute('data-swal-confirm') || 'Data yang dihapus tidak dapat dikembalikan.';

        swal({
            title: 'Yakin ingin menghapus?',
            text: message,
            icon: 'warning',
            buttons: ['Batal', 'Ya, Hapus'],
            dangerMode: true
        }).then(function (confirmed) {
            if (!confirmed) {
                return;
            }

            if (!link.hasAttribute('data-swal-ajax') || typeof window.fetch !== 'function') {
                window.location.href = link.href;
                return;
            }

            link.setAttribute('aria-disabled', 'true');
            window.fetch(link.href, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Data gagal dihapus.');
                    }
                    return data;
                });
            })
            .then(function (data) {
                var row = link.closest('tr');
                var tbody = row ? row.parentElement : null;
                if (row) row.remove();

                if (tbody) {
                    var rows = tbody.querySelectorAll('tr[data-work-row]');
                    rows.forEach(function (item, index) {
                        var numberCell = item.querySelector('td');
                        if (numberCell) numberCell.textContent = index + 1;
                    });

                    if (rows.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" style="padding:30px;">Belum ada karya yang diunggah.</td></tr>';
                    }
                }

                swal({
                    title: 'Berhasil Dihapus!',
                    text: data.message,
                    icon: 'success',
                    button: false,
                    timer: 3000,
                    closeOnClickOutside: false
                });
            })
            .catch(function (error) {
                link.removeAttribute('aria-disabled');
                swal('Gagal Menghapus', error.message || 'Data gagal dihapus.', 'error');
            });
        });
    });
})();
