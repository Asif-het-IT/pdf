(function () {
    var form = document.getElementById('toolForm');
    var result = document.getElementById('result');
    if (!form || !result) {
        return;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        result.classList.add('d-none');
        result.className = '';

        var data = new FormData(form);

        try {
            var response = await fetch(form.action, {
                method: 'POST',
                body: data,
                credentials: 'same-origin'
            });

            var json = await response.json();

            if (!response.ok || !json.ok) {
                throw new Error(json.message || 'Tool request failed');
            }

            var html = '<div class="alert alert-success">Processing complete.</div>';
            html += '<p><a class="btn btn-primary" href="' + json.download_url + '">Download Result</a></p>';

            if (json.metrics) {
                html += '<pre class="small bg-light p-3 rounded">' + JSON.stringify(json.metrics, null, 2) + '</pre>';
            }

            if (json.note) {
                html += '<div class="alert alert-warning small">' + json.note + '</div>';
            }

            if (json.preview_text) {
                html += '<div class="card p-3"><h3 class="h6">OCR Preview</h3><pre class="small">' + json.preview_text.replace(/</g, '&lt;') + '</pre></div>';
            }

            result.innerHTML = html;
            result.classList.remove('d-none');
        } catch (error) {
            result.innerHTML = '<div class="alert alert-danger">' + (error.message || 'Unexpected error') + '</div>';
            result.classList.remove('d-none');
        }
    });
})();
