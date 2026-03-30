@once('eclick-media-image-preview-modal-assets')
    {{-- Filament’s Tailwind build does not scan this view: layout must use plain CSS, not utility classes. --}}
    <style id="eclick-media-image-preview-styles">
        #eclick-media-image-modal {
            position: fixed !important;
            inset: 0 !important;
            z-index: 100000 !important;
            display: none;
            box-sizing: border-box;
            width: 100vw;
            width: 100dvw;
            height: 100vh;
            height: 100dvh;
            max-height: 100dvh;
            flex-direction: column;
            margin: 0;
            padding: 0;
            font-family: inherit;
        }

        #eclick-media-image-modal.eclick-media-image-modal--open {
            display: flex !important;
        }

        #eclick-media-image-modal [data-eclick-image-modal-backdrop] {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: 0;
            padding: 0;
            margin: 0;
            cursor: default;
            background: rgb(3 7 18 / 0.5);
        }

        .dark #eclick-media-image-modal [data-eclick-image-modal-backdrop] {
            background: rgb(3 7 18 / 0.75);
        }

        #eclick-media-image-modal .eclick-media-image-modal__center {
            pointer-events: none;
            position: relative;
            flex: 1 1 0;
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        @media (min-width: 640px) {
            #eclick-media-image-modal .eclick-media-image-modal__center {
                padding: 1.5rem;
            }
        }

        #eclick-media-image-modal .eclick-media-image-modal__window {
            pointer-events: auto;
            position: relative;
            width: 100%;
            max-width: 960px;
            border-radius: 0.75rem;
            background: #fff;
            box-shadow:
                0 25px 50px -12px rgb(0 0 0 / 0.25),
                0 0 0 1px rgb(3 7 18 / 0.05);
        }

        .dark #eclick-media-image-modal .eclick-media-image-modal__window {
            background: rgb(17 24 39);
            box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.5), 0 0 0 1px rgb(255 255 255 / 0.1);
        }

        #eclick-media-image-modal [data-eclick-close-image-modal] {
            position: absolute;
            top: 1rem;
            inset-inline-end: 1rem;
            z-index: 10;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            border: 0;
            border-radius: 0.5rem;
            background: transparent;
            color: rgb(156 163 175);
            cursor: pointer;
        }

        #eclick-media-image-modal [data-eclick-close-image-modal]:hover {
            background: rgb(107 114 128 / 0.08);
        }

        .dark #eclick-media-image-modal [data-eclick-close-image-modal] {
            color: rgb(107 114 128);
        }

        .dark #eclick-media-image-modal [data-eclick-close-image-modal]:hover {
            background: rgb(156 163 175 / 0.08);
        }

        #eclick-media-image-modal .eclick-media-image-modal__body {
            padding: 3.5rem 1rem 1.25rem;
        }

        @media (min-width: 640px) {
            #eclick-media-image-modal .eclick-media-image-modal__body {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
                padding-bottom: 1.5rem;
            }
        }

        #eclick-media-image-modal .eclick-media-image-modal__frame {
            display: flex;
            width: 100%;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 0.5rem;
            background: rgb(249 250 251);
            box-shadow: inset 0 0 0 1px rgb(3 7 18 / 0.05);
            height: min(75vh, 720px);
            min-height: 280px;
        }

        .dark #eclick-media-image-modal .eclick-media-image-modal__frame {
            background: rgb(255 255 255 / 0.05);
            box-shadow: inset 0 0 0 1px rgb(255 255 255 / 0.1);
        }

        #eclick-media-image-modal-img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: center;
        }

        #eclick-media-image-modal .eclick-media-image-modal__x-icon {
            width: 1.5rem;
            height: 1.5rem;
            flex-shrink: 0;
            display: block;
        }
    </style>
@endonce

@once('eclick-media-image-preview-modal-html')
    <div
        id="eclick-media-image-modal"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('messages.image_preview_dialog_label') }}"
        aria-hidden="true"
    >
        <button
            type="button"
            data-eclick-image-modal-backdrop
            tabindex="-1"
            aria-hidden="true"
        ></button>

        <div class="eclick-media-image-modal__center">
            <div class="eclick-media-image-modal__window">
                <button
                    type="button"
                    data-eclick-close-image-modal
                    aria-label="{{ __('messages.image_preview_close') }}"
                >
                    <svg class="eclick-media-image-modal__x-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="eclick-media-image-modal__body">
                    <div class="eclick-media-image-modal__frame">
                        <img
                            id="eclick-media-image-modal-img"
                            src=""
                            alt=""
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
@endonce
