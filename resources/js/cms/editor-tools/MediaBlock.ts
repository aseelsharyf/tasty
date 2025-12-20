import type { BlockTool, BlockToolConstructorOptions, API, BlockToolData } from '@editorjs/editorjs';

/**
 * Gap size options for media grid
 */
export type GapSize = 'none' | 'xs' | 'sm' | 'md' | 'lg' | 'xl';

/**
 * Display width options for media block
 */
export type DisplayWidth = 'default' | 'fullScreen';

/**
 * Media data structure for Editor.js block
 */
export interface MediaBlockData {
    items: MediaBlockItem[];
    layout: 'single' | 'grid' | 'carousel';
    gridColumns: number; // 1-12
    gap: GapSize;
    displayWidth: DisplayWidth; // default (content width) or fullScreen (edge to edge)
}

export interface CropVersion {
    id: number;
    uuid: string;
    preset_name: string;
    preset_label: string;
    label: string | null;
    display_label: string;
    url: string;
    thumbnail_url: string | null;
}

export interface MediaBlockItem {
    id: number;
    uuid: string;
    url: string;
    original_url?: string; // Store original URL when crop is selected
    thumbnail_url: string | null;
    title: string | null;
    alt_text: string | null;
    caption: string | null;
    credit_display: {
        name: string;
        url: string | null;
        role: string | null;
    } | null;
    is_image: boolean;
    is_video: boolean;
    // Available crop versions from media library
    crops?: CropVersion[];
    // Currently selected crop version
    crop_version?: CropVersion | null;
}

interface MediaBlockConfig {
    onSelectMedia?: (options: { multiple: boolean }) => Promise<MediaBlockItem[] | null>;
    placeholder?: string;
}

/**
 * Custom Editor.js block for embedding media from the media library
 */
export default class MediaBlock implements BlockTool {
    private api: API;
    private data: MediaBlockData;
    private config: MediaBlockConfig;
    private wrapper: HTMLElement | null = null;
    private readOnly: boolean;

    static get toolbox() {
        return {
            title: 'Media',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>',
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    constructor({ data, config, api, readOnly }: BlockToolConstructorOptions<MediaBlockData, MediaBlockConfig>) {
        this.api = api;
        this.config = config || {};
        this.readOnly = readOnly || false;
        this.data = this.normalizeData(data);
    }

    private normalizeData(data: BlockToolData): MediaBlockData {
        const normalized: MediaBlockData = {
            items: [],
            layout: 'single',
            gridColumns: 3,
            gap: 'md',
            displayWidth: 'default',
        };

        if (data && typeof data === 'object') {
            if (Array.isArray(data.items)) {
                normalized.items = data.items;
            }
            if (data.layout && ['single', 'grid', 'carousel'].includes(data.layout)) {
                normalized.layout = data.layout;
            }
            // Support columns 1-12
            if (data.gridColumns && typeof data.gridColumns === 'number') {
                normalized.gridColumns = Math.max(1, Math.min(12, data.gridColumns));
            } else if (typeof data.gridColumns === 'string') {
                const cols = parseInt(data.gridColumns, 10);
                if (!isNaN(cols)) {
                    normalized.gridColumns = Math.max(1, Math.min(12, cols));
                }
            }
            // Gap size
            if (data.gap && ['none', 'xs', 'sm', 'md', 'lg', 'xl'].includes(data.gap)) {
                normalized.gap = data.gap;
            }
            // Display width
            if (data.displayWidth && ['default', 'fullScreen'].includes(data.displayWidth)) {
                normalized.displayWidth = data.displayWidth;
            }
        }

        return normalized;
    }

    render(): HTMLElement {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('ce-media-block');

        if (this.data.items.length === 0) {
            this.renderPlaceholder();
        } else {
            this.renderMedia();
        }

        return this.wrapper;
    }

    private renderPlaceholder(): void {
        if (!this.wrapper) return;

        this.wrapper.innerHTML = '';

        const placeholder = document.createElement('div');
        placeholder.classList.add('ce-media-block__placeholder');
        placeholder.innerHTML = `
            <div class="ce-media-block__placeholder-content">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                    <circle cx="9" cy="9" r="2"/>
                    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                </svg>
                <span>${this.config.placeholder || 'Click to add media'}</span>
            </div>
        `;

        if (!this.readOnly) {
            placeholder.addEventListener('click', () => this.selectMedia(false));
        }

        this.wrapper.appendChild(placeholder);
    }

    private renderMedia(): void {
        if (!this.wrapper) return;

        this.wrapper.innerHTML = '';

        const container = document.createElement('div');
        container.classList.add('ce-media-block__container');
        container.classList.add(`ce-media-block__container--${this.data.layout}`);
        container.classList.add(`ce-media-block__container--gap-${this.data.gap}`);

        // Apply grid columns via inline style for flexibility (1-12)
        if (this.data.layout === 'grid') {
            container.style.setProperty('--grid-cols', String(this.data.gridColumns));
        }

        // Layout selector for multiple items
        if (this.data.items.length > 1 && !this.readOnly) {
            const layoutSelector = document.createElement('div');
            layoutSelector.classList.add('ce-media-block__layout-selector');

            // Create grid button
            const gridBtn = document.createElement('button');
            gridBtn.type = 'button';
            gridBtn.className = `ce-media-block__layout-btn ${this.data.layout === 'grid' ? 'active' : ''}`;
            gridBtn.title = 'Grid';
            gridBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
            `;
            gridBtn.addEventListener('mousedown', (e) => e.stopPropagation());
            gridBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.data.layout = 'grid';
                this.renderMedia();
            });

            // Create carousel (horizontal scroll) button
            const carouselBtn = document.createElement('button');
            carouselBtn.type = 'button';
            carouselBtn.className = `ce-media-block__layout-btn ${this.data.layout === 'carousel' ? 'active' : ''}`;
            carouselBtn.title = 'Horizontal Scroll';
            carouselBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            `;
            carouselBtn.addEventListener('mousedown', (e) => e.stopPropagation());
            carouselBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.data.layout = 'carousel';
                this.renderMedia();
            });

            layoutSelector.appendChild(gridBtn);
            layoutSelector.appendChild(carouselBtn);

            // Grid/Carousel options
            if (this.data.layout === 'grid' || this.data.layout === 'carousel') {
                const separator = document.createElement('span');
                separator.classList.add('ce-media-block__layout-separator');
                layoutSelector.appendChild(separator);

                // Columns selector (dropdown for 1-12)
                const colsLabel = document.createElement('span');
                colsLabel.classList.add('ce-media-block__cols-label');
                colsLabel.textContent = 'Cols:';
                layoutSelector.appendChild(colsLabel);

                const colsSelect = document.createElement('select');
                colsSelect.className = 'ce-media-block__select';
                colsSelect.addEventListener('mousedown', (e) => e.stopPropagation());
                colsSelect.addEventListener('change', (e) => {
                    e.stopPropagation();
                    this.data.gridColumns = parseInt((e.target as HTMLSelectElement).value, 10);
                    this.renderMedia();
                });

                for (let i = 1; i <= 12; i++) {
                    const option = document.createElement('option');
                    option.value = String(i);
                    option.textContent = String(i);
                    option.selected = this.data.gridColumns === i;
                    colsSelect.appendChild(option);
                }
                layoutSelector.appendChild(colsSelect);

                // Gap selector
                const gapSeparator = document.createElement('span');
                gapSeparator.classList.add('ce-media-block__layout-separator');
                layoutSelector.appendChild(gapSeparator);

                const gapLabel = document.createElement('span');
                gapLabel.classList.add('ce-media-block__cols-label');
                gapLabel.textContent = 'Gap:';
                layoutSelector.appendChild(gapLabel);

                const gapSelect = document.createElement('select');
                gapSelect.className = 'ce-media-block__select';
                gapSelect.addEventListener('mousedown', (e) => e.stopPropagation());
                gapSelect.addEventListener('change', (e) => {
                    e.stopPropagation();
                    this.data.gap = (e.target as HTMLSelectElement).value as GapSize;
                    this.renderMedia();
                });

                const gapOptions: { value: GapSize; label: string }[] = [
                    { value: 'none', label: 'None' },
                    { value: 'xs', label: 'XS' },
                    { value: 'sm', label: 'SM' },
                    { value: 'md', label: 'MD' },
                    { value: 'lg', label: 'LG' },
                    { value: 'xl', label: 'XL' },
                ];

                gapOptions.forEach(({ value, label }) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = label;
                    option.selected = this.data.gap === value;
                    gapSelect.appendChild(option);
                });
                layoutSelector.appendChild(gapSelect);
            }

            this.wrapper.appendChild(layoutSelector);
        }

        // Render items
        this.data.items.forEach((item, index) => {
            const itemEl = this.renderMediaItem(item, index);
            container.appendChild(itemEl);
        });

        this.wrapper.appendChild(container);

        // Add more button
        if (!this.readOnly) {
            const addMoreBtn = document.createElement('button');
            addMoreBtn.type = 'button';
            addMoreBtn.classList.add('ce-media-block__add-more');
            addMoreBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14m-7-7h14"/>
                </svg>
                <span>Add more media</span>
            `;
            addMoreBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.selectMedia(true);
            });
            this.wrapper.appendChild(addMoreBtn);
        }
    }

    private renderMediaItem(item: MediaBlockItem, index: number): HTMLElement {
        const itemEl = document.createElement('div');
        itemEl.classList.add('ce-media-block__item');

        // Image/Video container
        const mediaContainer = document.createElement('div');
        mediaContainer.classList.add('ce-media-block__media');

        // Check is_video strictly - ensure it's a boolean true, not just truthy
        const isVideo = item.is_video === true;

        if (isVideo) {
            mediaContainer.innerHTML = `
                <div class="ce-media-block__video-placeholder">
                    <img src="${item.thumbnail_url || ''}" alt="${item.alt_text || 'Video'}" />
                    <div class="ce-media-block__video-play">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                            <polygon points="5 3 19 12 5 21 5 3"/>
                        </svg>
                    </div>
                </div>
            `;
        } else {
            mediaContainer.innerHTML = `
                <img src="${item.url || item.thumbnail_url || ''}" alt="${item.alt_text || ''}" />
            `;
        }

        itemEl.appendChild(mediaContainer);

        // Crop selector (only for images with available crops)
        if (!isVideo && item.crops && item.crops.length > 0 && !this.readOnly) {
            const cropSelector = this.renderCropSelector(item, index);
            itemEl.appendChild(cropSelector);
        }

        // Caption/Credit section
        const infoSection = document.createElement('div');
        infoSection.classList.add('ce-media-block__info');

        // Editable caption - pre-populate from media library caption if not already set
        const captionEl = document.createElement('div');
        captionEl.classList.add('ce-media-block__caption');
        captionEl.contentEditable = this.readOnly ? 'false' : 'true';
        captionEl.dataset.placeholder = 'Add caption...';
        // Use the item's caption (which comes from media library initially)
        const captionText = item.caption || '';
        captionEl.textContent = captionText;
        captionEl.addEventListener('input', () => {
            this.data.items[index].caption = captionEl.textContent || null;
        });
        // Prevent editor from capturing focus events
        captionEl.addEventListener('keydown', (e) => {
            e.stopPropagation();
        });
        infoSection.appendChild(captionEl);

        // Credit display (non-editable, from media library)
        if (item.credit_display) {
            const creditEl = document.createElement('div');
            creditEl.classList.add('ce-media-block__credit');
            const creditText = item.credit_display.role
                ? `${item.credit_display.role}: ${item.credit_display.name}`
                : item.credit_display.name;

            if (item.credit_display.url) {
                creditEl.innerHTML = `<a href="${item.credit_display.url}" target="_blank" rel="noopener">${creditText}</a>`;
            } else {
                creditEl.textContent = creditText;
            }
            infoSection.appendChild(creditEl);
        }

        itemEl.appendChild(infoSection);

        // Remove button
        if (!this.readOnly) {
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.classList.add('ce-media-block__remove');
            removeBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            `;
            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.removeItem(index);
            });
            itemEl.appendChild(removeBtn);
        }

        return itemEl;
    }

    private renderCropSelector(item: MediaBlockItem, index: number): HTMLElement {
        const selector = document.createElement('div');
        selector.classList.add('ce-media-block__crop-selector');

        const currentLabel = item.crop_version?.display_label || 'Original';

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.classList.add('ce-media-block__crop-btn');
        btn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 2v14a2 2 0 0 0 2 2h14"/><path d="M18 22V8a2 2 0 0 0-2-2H2"/>
            </svg>
            <span>${currentLabel}</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m6 9 6 6 6-6"/>
            </svg>
        `;

        const dropdown = document.createElement('div');
        dropdown.classList.add('ce-media-block__crop-dropdown');
        dropdown.style.display = 'none';

        // Original option
        const originalOpt = document.createElement('button');
        originalOpt.type = 'button';
        originalOpt.classList.add('ce-media-block__crop-option');
        if (!item.crop_version) {
            originalOpt.classList.add('active');
        }
        originalOpt.textContent = 'Original';
        originalOpt.addEventListener('click', (e) => {
            e.stopPropagation();
            this.selectCrop(index, null);
            dropdown.style.display = 'none';
        });
        dropdown.appendChild(originalOpt);

        // Crop options
        item.crops?.forEach((crop) => {
            const opt = document.createElement('button');
            opt.type = 'button';
            opt.classList.add('ce-media-block__crop-option');
            if (item.crop_version?.uuid === crop.uuid) {
                opt.classList.add('active');
            }
            opt.textContent = crop.display_label;
            opt.addEventListener('click', (e) => {
                e.stopPropagation();
                this.selectCrop(index, crop);
                dropdown.style.display = 'none';
            });
            dropdown.appendChild(opt);
        });

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', () => {
            dropdown.style.display = 'none';
        }, { once: true });

        selector.appendChild(btn);
        selector.appendChild(dropdown);

        return selector;
    }

    private selectCrop(index: number, crop: CropVersion | null): void {
        const item = this.data.items[index];
        if (!item) return;

        // Store original URL if not already stored
        if (!item.original_url) {
            item.original_url = item.url;
        }

        if (crop) {
            // Use crop URL
            item.url = crop.url;
            item.crop_version = crop;
        } else {
            // Revert to original
            item.url = item.original_url;
            item.crop_version = null;
        }

        this.renderMedia();
    }

    private async selectMedia(multiple: boolean): Promise<void> {
        if (!this.config.onSelectMedia) {
            console.warn('MediaBlock: onSelectMedia callback not configured');
            return;
        }

        try {
            const selected = await this.config.onSelectMedia({ multiple });
            if (selected && selected.length > 0) {
                // Filter out items already in the block (prevent duplicates)
                const existingUuids = new Set(this.data.items.map(item => item.uuid));
                const newItems = selected.filter(item => !existingUuids.has(item.uuid));

                if (multiple) {
                    // Add new unique items to existing items
                    if (newItems.length > 0) {
                        this.data.items = [...this.data.items, ...newItems];
                        if (this.data.items.length > 1) {
                            this.data.layout = 'grid';
                        }
                    }
                } else {
                    // Replace items (for initial selection, use first selected item)
                    this.data.items = selected.slice(0, 1);
                    this.data.layout = 'single';
                }
                this.renderMedia();
            }
        } catch (error) {
            console.error('MediaBlock: Failed to select media', error);
        }
    }

    private removeItem(index: number): void {
        this.data.items.splice(index, 1);
        if (this.data.items.length === 0) {
            this.renderPlaceholder();
        } else if (this.data.items.length === 1) {
            this.data.layout = 'single';
            this.renderMedia();
        } else {
            this.renderMedia();
        }
    }

    save(): MediaBlockData {
        return this.data;
    }

    validate(savedData: MediaBlockData): boolean {
        // A valid block must have at least one media item
        return savedData.items && savedData.items.length > 0;
    }

    renderSettings(): HTMLElement {
        const wrapper = document.createElement('div');
        wrapper.classList.add('ce-media-block__settings');

        // Display width settings
        const displayWidthOptions: Array<{ value: DisplayWidth; icon: string; label: string }> = [
            {
                value: 'default',
                label: 'Content Width',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="12" height="16" rx="2"/></svg>',
            },
            {
                value: 'fullScreen',
                label: 'Full Screen Width',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/></svg>',
            },
        ];

        displayWidthOptions.forEach(({ value, icon, label }) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.classList.add('cdx-settings-button');
            button.classList.toggle('cdx-settings-button--active', this.data.displayWidth === value);
            button.innerHTML = icon;
            button.title = label;
            button.addEventListener('click', () => {
                this.data.displayWidth = value;
                // Update active state
                wrapper.querySelectorAll('.cdx-settings-button').forEach((btn) => {
                    btn.classList.remove('cdx-settings-button--active');
                });
                button.classList.add('cdx-settings-button--active');
            });
            wrapper.appendChild(button);
        });

        return wrapper;
    }
}
