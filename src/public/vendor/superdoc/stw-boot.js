/**
 * Boot SuperDoc untuk halaman Surat Tugas Word.
 * Dimuat sebagai module file statis (bukan inline) supaya pasti dieksekusi
 * browser, termasuk saat halaman dirender oleh Livewire/Filament.
 */

(() => {
    if (window.__stwBooted) {
        return;
    }

    window.__stwBooted = true;

    const configEl = document.getElementById('stw-config');
    const mountPoint = document.getElementById('stw-editor');
    const statusEl = document.querySelector('[data-stw-status]');
    const loadingEl = document.querySelector('[data-stw-loading]');

    if (!mountPoint || !configEl) {
        return;
    }

    const config = JSON.parse(configEl.textContent || '{}');

    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';

    const report = (stage, message, detail) => {
        try {
            const body = new URLSearchParams({ stage, message: message ?? '', detail: detail ?? '' });

            fetch(config.debugUrl, {
                method: 'POST',
                credentials: 'same-origin',
                keepalive: true,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json',
                },
                body: body.toString(),
            }).catch(() => {});
        } catch (e) {
            // abaikan
        }
    };

    window.addEventListener('error', (event) => {
        report('window-error', event.message, (event.filename || '') + ':' + (event.lineno || 0));
    });

    const setStatus = (text, tone = '') => {
        if (!statusEl) return;
        statusEl.textContent = text;
        statusEl.dataset.tone = tone;
    };

    const fail = (message, detail) => {
        setStatus(message, 'error');
        report('fail', message, detail);
        console.error('[surat-tugas-word]', message, detail ?? '');

        // Tampilkan pesan di area editor supaya tidak "muter" tanpa info.
        if (loadingEl) {
            loadingEl.innerHTML = '<div class="stw-error-box"><strong>Editor gagal dimuat</strong>'
                + '<span></span></div>';
            const span = loadingEl.querySelector('span');
            if (span) {
                span.textContent = message;
            }

            loadingEl.style.background = 'rgba(254, 226, 226, .96)';
            loadingEl.style.color = '#991b1b';
        }
    };

    const previewFrame = document.getElementById('stw-preview');

    const refreshPreview = () => {
        if (!previewFrame) return;

        const separator = config.pdfUrl.includes('?') ? '&' : '?';
        previewFrame.src = config.pdfUrl + separator + 't=' + Date.now();
    };

    const readAsBlob = async (url) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: { Accept: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' },
        });

        if (!response.ok) {
            throw new Error('Gagal memuat dokumen (' + response.status + ').');
        }

        return response.blob();
    };

    const waitForSuperDoc = async (timeoutMs = 40000) => {
        const started = Date.now();

        while (!window.SuperDoc) {
            if (Date.now() - started > timeoutMs) {
                throw new Error('Editor SuperDoc gagal dimuat (bundle tidak tersedia).');
            }

            await new Promise((resolve) => setTimeout(resolve, 100));
        }

        return window.SuperDoc;
    };

    const boot = async () => {
        setStatus('Memuat editor… ' + (config.bootVersion || ''));
        report('boot-start', 'boot.js dijalankan', 'version=' + (config.bootVersion || '?'));

        try {
            const SuperDocClass = await waitForSuperDoc();
            report('engine-ready', 'SuperDoc tersedia');
            setStatus('Memuat dokumen…');

            const blob = await readAsBlob(config.docxUrl);
            const file = new File([blob], config.fileName + '.docx', {
                type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            });

            const superdoc = new SuperDocClass({
                selector: '#stw-editor',
                document: file,
                    user: { name: config.userName, email: config.userEmail },
                    documentMode: 'editing',
                    ui: {
                        toolbar: {
                            container: '#stw-sd-toolbar',
                            items: {
                                left: ['undo', 'redo', 'font-family', 'font-size'],
                                center: ['bold', 'italic', 'underline', 'text-color', 'highlight-color', 'text-align', 'bullet-list', 'numbered-list'],
                                right: ['zoom', 'search'],
                            },
                        },
                    },
                telemetry: { enabled: false },
                onReady: () => {
                    loadingEl?.remove();
                    setStatus('Siap diedit', 'ok');
                },
                onEditorUpdate: () => setStatus('Ada perubahan belum disimpan', 'dirty'),
                onContentError: ({ error }) => fail('Gagal membuka dokumen: ' + (error?.message ?? '')),
                onException: ({ error }) => fail('Editor bermasalah: ' + (error?.message ?? '')),
            });

            window.__stwSuperdoc = superdoc;

            document.querySelector('[data-stw-action="save"]')?.addEventListener('click', async (event) => {
                const button = event.currentTarget;
                button.disabled = true;
                setStatus('Menyimpan…');

                try {
                    const exported = await superdoc.export({ triggerDownload: false });
                    const bytes = exported instanceof Blob ? exported : new Blob([exported]);

                    const form = new FormData();
                    form.append('file', bytes, config.fileName + '.docx');

                    const response = await fetch(config.saveUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
                        body: form,
                    });

                    if (!response.ok) {
                        const data = await response.json().catch(() => ({}));
                        throw new Error(data.message ?? 'Gagal menyimpan (' + response.status + ').');
                    }

                    setStatus('Tersimpan', 'ok');
                    refreshPreview();
                    window.__stwShowToast?.('Dokumen tersimpan.');
                } catch (error) {
                    fail('Gagal menyimpan: ' + error.message);
                } finally {
                    button.disabled = false;
                }
            });

            document.querySelector('[data-stw-action="print"]')?.addEventListener('click', async (event) => {
                const button = event.currentTarget;
                button.disabled = true;
                setStatus('Menyiapkan cetak…');

                try {
                    const exported = await superdoc.export({ triggerDownload: false });
                    const bytes = exported instanceof Blob ? exported : new Blob([exported]);

                    const form = new FormData();
                    form.append('file', bytes, config.fileName + '.docx');

                    // PDF dari isi kanvas (termasuk edit belum disimpan),
                    // dirender LibreOffice supaya sama dengan hasil unduhan.
                    const response = await fetch(config.printUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/pdf' },
                        body: form,
                    });

                    if (!response.ok) {
                        throw new Error('Gagal menyiapkan cetak (' + response.status + ').');
                    }

                    const url = URL.createObjectURL(await response.blob());
                    const win = window.open(url, '_blank');

                    if (!win) {
                        throw new Error('Popup diblokir browser. Izinkan popup lalu coba lagi.');
                    }

                    setStatus('PDF cetak dibuka, tekan Ctrl+P', 'ok');
                    setTimeout(() => URL.revokeObjectURL(url), 120000);
                } catch (error) {
                    fail('Gagal mencetak: ' + error.message);
                } finally {
                    button.disabled = false;
                }
            });

            document.querySelector('[data-stw-action="download"]')?.addEventListener('click', async () => {
                try {
                    await superdoc.export({ exportedName: config.fileName });
                    setStatus('Diunduh', 'ok');
                } catch (error) {
                    fail('Gagal mengunduh: ' + error.message);
                }
            });

            document.querySelector('[data-stw-action="regenerate"]')?.addEventListener('click', async () => {
                setStatus('Generate ulang…');

                try {
                    const response = await fetch(config.regenerateUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
                    });

                    if (!response.ok) {
                        throw new Error('Gagal generate ulang (' + response.status + ').');
                    }

                    // replaceFile() tidak menerapkan ulang ukuran halaman
                    // (pgSz) pada kanvas, sehingga dokumen hasil generate bisa
                    // tampil A4 walau berkas barunya folio. Muat ulang halaman
                    // supaya kanvas membaca berkas baru dari awal.
                    setStatus('Dokumen digenerate ulang, memuat ulang…', 'ok');
                    window.location.reload();

                    return;
                } catch (error) {
                    fail('Gagal generate ulang: ' + error.message);
                }
            });
        } catch (error) {
            fail(error.message);
        }
    };

    window.__stwShowToast = (message) => {
        const toast = document.createElement('div');
        toast.className = 'stw-toast';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2500);
    };

    const tabs = Array.from(document.querySelectorAll('[data-stw-tab]'));
    const panels = {
        preview: document.querySelector('[data-stw-panel="preview"]'),
        edit: document.querySelector('[data-stw-panel="edit"]'),
    };
    const editOnly = Array.from(document.querySelectorAll('[data-stw-edit-only]'));

    let editorStarted = false;

    const showTab = (name) => {
        tabs.forEach((tab) => tab.classList.toggle('is-active', tab.dataset.stwTab === name));

        Object.entries(panels).forEach(([key, panel]) => {
            if (panel) {
                panel.hidden = key !== name;
            }
        });

        editOnly.forEach((element) => {
            element.style.display = name === 'edit' ? '' : 'none';
        });

        // Kanvas SuperDoc baru dibuat saat tab Edit dipilih (butuh container
        // yang terlihat agar ukurannya benar).
        if (name === 'edit' && !editorStarted) {
            editorStarted = true;
            boot();
        }
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => showTab(tab.dataset.stwTab));
    });

    if (tabs.length > 0) {
        showTab('preview');
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
