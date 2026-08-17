(function (root, factory) {
    const api = factory();

    if (typeof module === 'object' && module.exports) {
        module.exports = api;
    }

    if (root && root.document && root.jQuery) {
        api.bind(root);
    }
})(typeof window !== 'undefined' ? window : null, function () {
    'use strict';

    function canvasOptions(state) {
        return {
            width: Number(state.width),
            height: Number(state.height),
        };
    }

    function replaceInputFile(input, originalFile, blob, DataTransferType, FileType, lastModified) {
        const transfer = new DataTransferType();
        const croppedFile = new FileType([blob], originalFile.name, {
            type: originalFile.type || blob.type || 'image/jpeg',
            lastModified: lastModified || Date.now(),
        });

        transfer.items.add(croppedFile);
        input.files = transfer.files;

        return croppedFile;
    }

    function clearSelection(input) {
        if (input) {
            input.value = '';
        }
    }

    function bind(root) {
        const $ = root.jQuery;
        const state = {
            input: null,
            file: null,
            cropper: null,
            objectUrl: null,
            width: 0,
            height: 0,
            baseZoom: 1,
            zoomPercent: 100,
        };

        let dialog = null;

        function notify(message) {
            if (typeof root.error === 'function') {
                root.error(message);
                return;
            }

            root.alert(message);
        }

        function ensureDialog() {
            if (dialog) {
                return dialog;
            }

            const wrapper = root.document.createElement('div');
            wrapper.id = 'form-tool-cropper-dialog';
            wrapper.className = 'form-tool-cropper-dialog';
            wrapper.hidden = true;
            wrapper.setAttribute('aria-hidden', 'true');
            wrapper.innerHTML = `
                <div class="form-tool-cropper-panel" role="dialog" aria-modal="true" aria-labelledby="form-tool-cropper-title">
                    <div class="form-tool-cropper-header">
                        <strong id="form-tool-cropper-title">Crop image</strong>
                        <button type="button" class="form-tool-cropper-close" data-crop-action="cancel" aria-label="Cancel crop">&times;</button>
                    </div>
                    <div class="form-tool-cropper-workspace">
                        <img class="form-tool-cropper-image" alt="Selected image to crop">
                    </div>
                    <div class="form-tool-cropper-footer">
                        <div class="form-tool-cropper-tools">
                            <button type="button" data-crop-action="rotate">Rotate</button>
                            <button type="button" data-crop-action="zoom-out">Zoom out</button>
                            <span class="form-tool-cropper-zoom" aria-live="polite">100%</span>
                            <button type="button" data-crop-action="zoom-in">Zoom in</button>
                            <button type="button" data-crop-action="reset">Reset</button>
                        </div>
                        <div class="form-tool-cropper-actions">
                            <button type="button" data-crop-action="cancel">Cancel</button>
                            <button type="button" class="form-tool-cropper-apply" data-crop-action="apply">Crop</button>
                        </div>
                    </div>
                </div>`;

            root.document.body.appendChild(wrapper);
            dialog = wrapper;

            $(dialog).on('click', '[data-crop-action]', function () {
                handleAction(this.getAttribute('data-crop-action'));
            });

            $(dialog).on('click', function (event) {
                if (event.target === dialog) {
                    event.preventDefault();
                }
            });

            return dialog;
        }

        function updateZoomLabel() {
            if (dialog) {
                dialog.querySelector('.form-tool-cropper-zoom').textContent = state.zoomPercent + '%';
            }
        }

        function setZoom(percent) {
            if (! state.cropper) {
                return;
            }

            state.zoomPercent = Math.max(100, Math.min(200, percent));
            state.cropper.zoomTo(state.baseZoom * (state.zoomPercent / 100));
            updateZoomLabel();
        }

        function destroySession(clearInput) {
            const activeInput = state.input;

            if (state.cropper) {
                state.cropper.destroy();
            }

            if (state.objectUrl) {
                root.URL.revokeObjectURL(state.objectUrl);
            }

            if (clearInput) {
                clearSelection(activeInput);
            }

            state.input = null;
            state.file = null;
            state.cropper = null;
            state.objectUrl = null;
            state.width = 0;
            state.height = 0;
            state.baseZoom = 1;
            state.zoomPercent = 100;

            if (dialog) {
                dialog.hidden = true;
                dialog.setAttribute('aria-hidden', 'true');
                dialog.querySelector('.form-tool-cropper-image').removeAttribute('src');
            }

            root.document.body.classList.remove('form-tool-cropper-open');
        }

        function failSession(message) {
            destroySession(true);
            notify(message);
        }

        function resetCropper() {
            if (! state.cropper) {
                return;
            }

            state.cropper.reset();
            const imageData = state.cropper.getImageData();
            state.baseZoom = imageData.naturalWidth ? imageData.width / imageData.naturalWidth : 1;
            setZoom(100);
        }

        function applyCrop() {
            if (! state.cropper || ! state.input || ! state.file) {
                return;
            }

            const canvas = state.cropper.getCroppedCanvas(canvasOptions(state));
            if (! canvas) {
                failSession('The selected image could not be cropped.');
                return;
            }

            const input = state.input;
            const originalFile = state.file;
            const outputType = originalFile.type || 'image/jpeg';

            canvas.toBlob(function (blob) {
                if (! blob) {
                    failSession('The selected image could not be cropped.');
                    return;
                }

                const croppedFile = replaceInputFile(
                    input,
                    originalFile,
                    blob,
                    root.DataTransfer,
                    root.File,
                );

                destroySession(false);
                $(input).trigger('formtool:cropped', [croppedFile]);
            }, outputType, 0.92);
        }

        function handleAction(action) {
            if (action === 'cancel') {
                destroySession(true);
            } else if (action === 'rotate' && state.cropper) {
                state.cropper.rotate(90);
            } else if (action === 'zoom-out') {
                setZoom(state.zoomPercent - 10);
            } else if (action === 'zoom-in') {
                setZoom(state.zoomPercent + 10);
            } else if (action === 'reset') {
                resetCropper();
            } else if (action === 'apply') {
                applyCrop();
            }
        }

        function openCropper(input, file) {
            if (
                typeof root.Cropper === 'undefined'
                || typeof root.DataTransfer === 'undefined'
                || typeof root.File === 'undefined'
                || typeof root.FileReader === 'undefined'
                || ! root.URL
                || typeof root.URL.createObjectURL !== 'function'
            ) {
                clearSelection(input);
                notify('Image cropping is not supported in this browser.');
                return;
            }

            const width = Number(input.dataset.cropWidth);
            const height = Number(input.dataset.cropHeight);
            const maxSizeInKb = Number(input.dataset.maxSizeKb || 0);

            if (! file.type || file.type.indexOf('image/') !== 0 || width <= 0 || height <= 0) {
                clearSelection(input);
                notify('Please select a valid image.');
                return;
            }

            if (maxSizeInKb > 0 && file.size / 1024 > maxSizeInKb) {
                clearSelection(input);
                notify('File size cannot be more than ' + (maxSizeInKb / 1024) + 'MB.');
                return;
            }

            destroySession(false);
            ensureDialog();

            state.input = input;
            state.file = file;
            state.width = width;
            state.height = height;
            state.zoomPercent = 100;
            state.objectUrl = root.URL.createObjectURL(file);

            const image = dialog.querySelector('.form-tool-cropper-image');
            image.onload = function () {
                state.cropper = new root.Cropper(image, {
                    aspectRatio: state.width / state.height,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 1,
                    responsive: true,
                    background: false,
                    cropBoxMovable: true,
                    cropBoxResizable: false,
                    ready: function () {
                        const imageData = state.cropper.getImageData();
                        state.baseZoom = imageData.naturalWidth ? imageData.width / imageData.naturalWidth : 1;
                        state.zoomPercent = 100;
                        updateZoomLabel();
                    },
                });
            };
            image.onerror = function () {
                failSession('The selected image could not be read.');
            };
            image.src = state.objectUrl;

            dialog.hidden = false;
            dialog.setAttribute('aria-hidden', 'false');
            root.document.body.classList.add('form-tool-cropper-open');
            updateZoomLabel();
            dialog.querySelector('[data-crop-action="cancel"]').focus();
        }

        $(root.document).on('change', 'input[data-form-tool-crop="1"]', function () {
            const file = this.files && this.files[0];
            if (file) {
                openCropper(this, file);
            }
        });

        $(root.document).on('keydown', function (event) {
            if (event.key === 'Escape' && dialog && ! dialog.hidden) {
                destroySession(true);
            }
        });
    }

    return {
        bind: bind,
        canvasOptions: canvasOptions,
        clearSelection: clearSelection,
        replaceInputFile: replaceInputFile,
    };
});
