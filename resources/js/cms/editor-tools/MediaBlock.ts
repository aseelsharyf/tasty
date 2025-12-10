import type { BlockTool, BlockToolConstructorOptions, API, BlockToolData } from '@editorjs/editorjs';

/**
 * Media data structure for Editor.js block
 */
export interface MediaBlockData {
    items: MediaBlockItem[];
    layout: 'single' | 'grid' | 'carousel';
    gridColumns: 2 | 3 | 4;
}

export interface MediaBlockItem {
    id: number;
    uuid: string;
    url: string;
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
    // Crop version info (when a cropped version is selected)
    crop_version?: {
        id: number;
        uuid: string;
        preset_name: string;
        preset_label: string;
        label: string | null;
        display_label: string;
    } | null;
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
        };

        if (data && typeof data === 'object') {
            if (Array.isArray(data.items)) {
                normalized.items = data.items;
            }
            if (data.layout && ['single', 'grid', 'carousel'].includes(data.layout)) {
                normalized.layout = data.layout;
            }
            if (data.gridColumns && [2, 3, 4].includes(data.gridColumns)) {
                normalized.gridColumns = data.gridColumns;
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
        if (this.data.layout === 'grid') {
            container.classList.add(`ce-media-block__container--cols-${this.data.gridColumns}`);
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

            // Create carousel button
            const carouselBtn = document.createElement('button');
            carouselBtn.type = 'button';
            carouselBtn.className = `ce-media-block__layout-btn ${this.data.layout === 'carousel' ? 'active' : ''}`;
            carouselBtn.title = 'Carousel';
            carouselBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01"/>
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

            // Grid columns selector (only show when grid layout is active)
            if (this.data.layout === 'grid') {
                const separator = document.createElement('span');
                separator.classList.add('ce-media-block__layout-separator');
                layoutSelector.appendChild(separator);

                const colsLabel = document.createElement('span');
                colsLabel.classList.add('ce-media-block__cols-label');
                colsLabel.textContent = 'Cols:';
                layoutSelector.appendChild(colsLabel);

                [2, 3, 4].forEach((cols) => {
                    const colBtn = document.createElement('button');
                    colBtn.type = 'button';
                    colBtn.className = `ce-media-block__layout-btn ce-media-block__cols-btn ${this.data.gridColumns === cols ? 'active' : ''}`;
                    colBtn.title = `${cols} columns`;
                    colBtn.textContent = String(cols);
                    colBtn.addEventListener('mousedown', (e) => e.stopPropagation());
                    colBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        this.data.gridColumns = cols as 2 | 3 | 4;
                        this.renderMedia();
                    });
                    layoutSelector.appendChild(colBtn);
                });
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
}
