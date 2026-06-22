(function () {
    window.submitBidAjax = function (form, onSuccess) {
        if (!form || typeof window.fetch !== 'function') {
            if (form) form.submit();
            return Promise.resolve(false);
        }

        var submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) submitButton.disabled = true;

        return window.fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok) {
                    throw new Error(data.message || 'Penawaran gagal diproses.');
                }
                return data;
            });
        })
        .then(function (data) {
            if (data.success && typeof onSuccess === 'function') {
                onSuccess(data);
            }

            swal({
                title: data.title,
                text: data.message,
                icon: data.icon,
                button: false,
                timer: 3000,
                closeOnClickOutside: false
            });

            return data.success;
        })
        .catch(function (error) {
            swal({
                title: 'Tawaran Gagal!',
                text: error.message || 'Penawaran gagal diproses.',
                icon: 'error',
                button: false,
                timer: 3000,
                closeOnClickOutside: false
            });
            return false;
        })
        .then(function (success) {
            if (submitButton) submitButton.disabled = false;
            return success;
        });
    };
})();
