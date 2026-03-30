/**
 * Image preview overlay. Visibility uses class eclick-media-image-modal--open + scoped CSS in the Blade partial
 * (Filament’s Tailwind build does not include arbitrary utilities from our views).
 */
const INIT_KEY = '__eclickMediaImagePreview';
const OPEN_CLASS = 'eclick-media-image-modal--open';

function getModal() {
    return document.getElementById('eclick-media-image-modal');
}

function getModalImg() {
    return document.getElementById('eclick-media-image-modal-img');
}

function isModalOpen() {
    const modal = getModal();
    return Boolean(modal?.classList.contains(OPEN_CLASS));
}

function mountModalOnBody() {
    const modal = getModal();
    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
}

function openModal(src) {
    if (!src || src.startsWith('data:')) {
        return;
    }
    mountModalOnBody();
    const modal = getModal();
    const img = getModalImg();
    if (!modal || !img) {
        return;
    }
    img.src = src;
    modal.classList.add(OPEN_CLASS);
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');
}

function closeModal() {
    const modal = getModal();
    const img = getModalImg();
    if (!modal || !img) {
        return;
    }
    modal.classList.remove(OPEN_CLASS);
    modal.setAttribute('aria-hidden', 'true');
    img.removeAttribute('src');
    document.body.classList.remove('overflow-hidden');
}

function shouldOpenPreviewForImage(img) {
    if (!(img instanceof HTMLImageElement)) {
        return false;
    }
    if (img.closest('#eclick-media-image-modal')) {
        return false;
    }
    if (img.closest('.fi-fo-file-upload-editor')) {
        return false;
    }
    if (img.classList.contains('eclick-open-image-preview')) {
        return true;
    }
    if (img.closest('.fi-receipt-edit-image-ctn')) {
        return true;
    }
    if (img.closest('.eclick-receipt-show')) {
        return true;
    }
    const inFileUpload =
        Boolean(img.closest('.fi-fo-file-upload')) &&
        !img.closest('.fi-fo-file-upload-editor');
    return inFileUpload;
}

function init() {
    if (window[INIT_KEY]) {
        return;
    }
    window[INIT_KEY] = true;

    document.addEventListener(
        'click',
        (e) => {
            const t = e.target;
            if (!(t instanceof HTMLImageElement)) {
                return;
            }
            if (!shouldOpenPreviewForImage(t)) {
                return;
            }
            const src = t.currentSrc || t.src;
            if (!src || src.startsWith('data:')) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            openModal(src);
        },
        true,
    );

    document.addEventListener('click', (e) => {
        if (e.target?.closest?.('[data-eclick-close-image-modal]')) {
            e.preventDefault();
            closeModal();
            return;
        }

        if (!isModalOpen()) {
            return;
        }

        if (e.target?.closest?.('[data-eclick-image-modal-backdrop]')) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
