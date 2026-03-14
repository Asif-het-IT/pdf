(function () {
    var form = document.getElementById('toolForm');
    var resultArea = document.getElementById('result');
    if (!form || !resultArea) return;

    var MAX_FILES = 20;
    var CONCURRENCY = 5;

    function esc(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function makeJobCard(index, fileName) {
        return '<div class="job-card mb-2" id="job-card-' + index + '">'
            + '<div class="d-flex justify-content-between align-items-center mb-1">'
            + '<span class="job-file-name small fw-semibold" title="' + esc(fileName) + '">' + esc(fileName) + '</span>'
            + '<span class="badge bg-secondary job-status-badge" id="job-badge-' + index + '">Queuing...</span>'
            + '</div>'
            + '<div class="progress" style="height:10px;border-radius:8px;" role="progressbar" '
            + '     aria-valuemin="0" aria-valuemax="100" aria-valuenow="4">'
            + '  <div class="progress-bar het-bar" id="job-bar-' + index + '" style="width:4%"></div>'
            + '</div>'
            + '<div class="job-download-area mt-2" id="job-dl-' + index + '"></div>'
            + '</div>';
    }

    function setBar(index, pct, status) {
        var bar   = document.getElementById('job-bar-'   + index);
        var badge = document.getElementById('job-badge-' + index);
        if (!bar || !badge) return;
        bar.style.width = pct + '%';
        bar.setAttribute('aria-valuenow', pct);
        if (status === 'completed') {
            bar.className = 'progress-bar bg-success';
            badge.textContent = 'Completed';
            badge.className   = 'badge bg-success job-status-badge';
        } else if (status === 'failed') {
            bar.className = 'progress-bar bg-danger';
            badge.textContent = 'Failed';
            badge.className   = 'badge bg-danger job-status-badge';
        } else if (status === 'processing') {
            badge.textContent = 'Processing...';
            badge.className   = 'badge bg-warning text-dark job-status-badge';
        } else if (status === 'queued') {
            badge.textContent = 'Queued';
            badge.className   = 'badge bg-info text-dark job-status-badge';
        }
    }

    function setDownload(index, url, errMsg) {
        var area = document.getElementById('job-dl-' + index);
        if (!area) return;
        if (url) {
            area.innerHTML = '<a class="btn btn-sm btn-primary" href="' + esc(url) + '">Download</a>';
        } else if (errMsg) {
            area.innerHTML = '<span class="text-danger small">' + esc(errMsg) + '</span>';
        }
    }

    async function pollOne(jobUuid, index) {
        var MAX_ROUNDS = 120;
        for (var r = 0; r < MAX_ROUNDS; r++) {
            await new Promise(function(res){ setTimeout(res, 2000); });
            try {
                var resp = await fetch('/api/jobs/status.php?id=' + encodeURIComponent(jobUuid), { credentials: 'same-origin' });
                var json = await resp.json();
                if (!resp.ok || !json.ok || !json.job) continue;
                var job = json.job;
                setBar(index, Number(job.progress || 0), job.status);
                if (job.status === 'completed') {
                    setDownload(index, job.download_url || null, null);
                    return;
                }
                if (job.status === 'failed') {
                    setDownload(index, null, job.error || 'Processing failed');
                    return;
                }
            } catch (_) { /* retry */ }
        }
        setBar(index, 100, 'failed');
        setDownload(index, null, 'Timeout: processing took too long');
    }

    async function queueOne(file, baseFormData, colIndex) {
        var multiInput = form.querySelector('input[type="file"][data-multi-job]');
        var inputName  = multiInput ? multiInput.name : 'pdf';

        var fd = new FormData();
        for (var pair of baseFormData.entries()) {
            if (typeof pair[1] === 'string') fd.append(pair[0], pair[1]);
        }
        fd.append(inputName, file, file.name);

        try {
            var resp = await fetch(form.action, { method: 'POST', body: fd, credentials: 'same-origin' });
            var json = await resp.json();
            if (!resp.ok || !json.ok || !json.job_uuid) {
                throw new Error(json.message || 'Queue failed');
            }
            setBar(colIndex, 12, 'queued');
            await pollOne(json.job_uuid, colIndex);
        } catch (err) {
            setBar(colIndex, 100, 'failed');
            setDownload(colIndex, null, err.message || 'Error');
        }
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        var multiInput = form.querySelector('input[type="file"][data-multi-job]');
        var files = multiInput && multiInput.files && multiInput.files.length > 0
                    ? Array.from(multiInput.files) : null;

        if (files && files.length > MAX_FILES) {
            resultArea.innerHTML = '<div class="alert alert-danger">Maximum ' + MAX_FILES + ' files allowed at once.</div>';
            resultArea.classList.remove('d-none');
            return;
        }

        var baseFormData = new FormData(form);

        if (files && files.length > 1) {
            // ---- BATCH MODE ----
            var html = '<div class="card panel p-3"><p class="fw-semibold mb-3">'
                + files.length + ' files queue ho rahe hain:</p>';
            files.forEach(function(f, i){ html += makeJobCard(i, f.name); });
            html += '</div>';
            resultArea.innerHTML = html;
            resultArea.classList.remove('d-none');
            resultArea.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            var idx = 0;
            async function runNext() {
                if (idx >= files.length) return;
                var i = idx++;
                await queueOne(files[i], baseFormData, i);
                await runNext();
            }
            var workers = [];
            for (var w = 0; w < Math.min(CONCURRENCY, files.length); w++) {
                workers.push(runNext());
            }
            await Promise.all(workers);

        } else {
            // ---- SINGLE FILE MODE ----
            var singleFile = files ? files[0] : null;
            var label = singleFile ? singleFile.name : 'File';
            resultArea.innerHTML = '<div class="card panel p-3">' + makeJobCard(0, label) + '</div>';
            resultArea.classList.remove('d-none');
            resultArea.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            try {
                var resp = await fetch(form.action, { method: 'POST', body: baseFormData, credentials: 'same-origin' });
                var json = await resp.json();
                if (!resp.ok || !json.ok || !json.job_uuid) {
                    throw new Error(json.message || 'Queue failed');
                }
                setBar(0, 12, 'queued');
                await pollOne(json.job_uuid, 0);
            } catch (err) {
                setBar(0, 100, 'failed');
                setDownload(0, null, err.message || 'Unexpected error');
            }
        }
    });
})();