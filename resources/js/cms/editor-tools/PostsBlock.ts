import type { BlockTool, BlockToolConstructorOptions, API, BlockToolData } from '@editorjs/editorjs';

/**
 * Post item structure for the block
 */
export interface PostBlockItem {
    id: number;
    uuid: string;
    title: string;
    slug: string;
    excerpt: string | null;
    featured_image_url: string | null;
    featured_image_thumb: string | null;
    url: string;
    author: {
        id: number;
        name: string;
    } | null;
    featured_tag: {
        id: number;
        name: string;
        slug: string;
    } | null;
    published_at: string | null;
}

/**
 * Posts block data structure
 */
export interface PostsBlockData {
    posts: PostBlockItem[];
    layout: 'grid' | 'scroll';
}

interface PostsBlockConfig {
    onSelectPosts?: () => Promise<PostBlockItem[] | null>;
    placeholder?: string;
}

/**
 * Custom Editor.js block for embedding posts
 */
export default class PostsBlock implements BlockTool {
    private api: API;
    private data: PostsBlockData;
    private config: PostsBlockConfig;
    private wrapper: HTMLElement | null = null;
    private readOnly: boolean;

    static get toolbox() {
        return {
            title: 'Posts',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/></svg>',
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    constructor({ data, config, api, readOnly }: BlockToolConstructorOptions<PostsBlockData, PostsBlockConfig>) {
        this.api = api;
        this.config = config || {};
        this.readOnly = readOnly || false;
        this.data = this.normalizeData(data);
    }

    private normalizeData(data: BlockToolData): PostsBlockData {
        const normalized: PostsBlockData = {
            posts: [],
            layout: 'grid',
        };

        if (data && typeof data === 'object') {
            if (Array.isArray(data.posts)) {
                normalized.posts = data.posts;
            }
            if (data.layout && ['grid', 'scroll'].includes(data.layout)) {
                normalized.layout = data.layout;
            }
        }

        return normalized;
    }

    render(): HTMLElement {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('ce-posts-block');

        if (this.data.posts.length === 0) {
            this.renderPlaceholder();
        } else {
            this.renderPosts();
        }

        return this.wrapper;
    }

    private renderPlaceholder(): void {
        if (!this.wrapper) return;

        this.wrapper.innerHTML = '';

        const placeholder = document.createElement('div');
        placeholder.classList.add('ce-posts-block__placeholder');
        placeholder.innerHTML = `
            <div class="ce-posts-block__placeholder-content">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/>
                    <path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/>
                </svg>
                <span>${this.config.placeholder || 'Click to add posts'}</span>
            </div>
        `;

        if (!this.readOnly) {
            placeholder.addEventListener('click', () => this.selectPosts());
        }

        this.wrapper.appendChild(placeholder);
    }

    private renderPosts(): void {
        if (!this.wrapper) return;

        this.wrapper.innerHTML = '';

        const container = document.createElement('div');
        container.classList.add('ce-posts-block__container');
        container.classList.add(`ce-posts-block__container--${this.data.layout}`);

        // Layout selector
        if (!this.readOnly) {
            const layoutSelector = document.createElement('div');
            layoutSelector.classList.add('ce-posts-block__layout-selector');

            // Grid layout button
            const gridBtn = document.createElement('button');
            gridBtn.type = 'button';
            gridBtn.className = `ce-posts-block__layout-btn ${this.data.layout === 'grid' ? 'active' : ''}`;
            gridBtn.title = 'Grid';
            gridBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>
            `;
            gridBtn.addEventListener('mousedown', (e) => e.stopPropagation());
            gridBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.data.layout = 'grid';
                this.renderPosts();
            });

            // Scroll layout button
            const scrollBtn = document.createElement('button');
            scrollBtn.type = 'button';
            scrollBtn.className = `ce-posts-block__layout-btn ${this.data.layout === 'scroll' ? 'active' : ''}`;
            scrollBtn.title = 'Horizontal Scroll';
            scrollBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="6" width="6" height="12" rx="1"/><rect x="9" y="6" width="6" height="12" rx="1"/>
                    <rect x="16" y="6" width="6" height="12" rx="1"/>
                </svg>
            `;
            scrollBtn.addEventListener('mousedown', (e) => e.stopPropagation());
            scrollBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.data.layout = 'scroll';
                this.renderPosts();
            });

            layoutSelector.appendChild(gridBtn);
            layoutSelector.appendChild(scrollBtn);

            this.wrapper.appendChild(layoutSelector);
        }

        // Render posts
        this.data.posts.forEach((post, index) => {
            const postEl = this.renderPostItem(post, index);
            container.appendChild(postEl);
        });

        this.wrapper.appendChild(container);

        // Add more button
        if (!this.readOnly) {
            const addMoreBtn = document.createElement('button');
            addMoreBtn.type = 'button';
            addMoreBtn.classList.add('ce-posts-block__add-more');
            addMoreBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14m-7-7h14"/>
                </svg>
                <span>Add more posts</span>
            `;
            addMoreBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.selectPosts();
            });
            this.wrapper.appendChild(addMoreBtn);
        }
    }

    private renderPostItem(post: PostBlockItem, index: number): HTMLElement {
        const itemEl = document.createElement('div');
        itemEl.classList.add('ce-posts-block__item');

        // Format date
        let dateStr = '';
        if (post.published_at) {
            const date = new Date(post.published_at);
            dateStr = date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });
        }

        itemEl.innerHTML = `
            <div class="ce-posts-block__image">
                ${post.featured_image_thumb
                    ? `<img src="${post.featured_image_thumb}" alt="${post.title}" />`
                    : `<div class="ce-posts-block__image-placeholder">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                            <circle cx="9" cy="9" r="2"/>
                            <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                        </svg>
                    </div>`
                }
            </div>
            <div class="ce-posts-block__content">
                ${post.featured_tag
                    ? `<span class="ce-posts-block__tag">${post.featured_tag.name}</span>`
                    : ''
                }
                <h4 class="ce-posts-block__title">${post.title}</h4>
                <div class="ce-posts-block__meta">
                    ${post.author
                        ? `<span class="ce-posts-block__author">BY ${post.author.name.toUpperCase()}</span>`
                        : ''
                    }
                    ${post.author && dateStr ? '<span class="ce-posts-block__separator">•</span>' : ''}
                    ${dateStr ? `<span class="ce-posts-block__date">${dateStr.toUpperCase()}</span>` : ''}
                </div>
            </div>
        `;

        // Remove button
        if (!this.readOnly) {
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.classList.add('ce-posts-block__remove');
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

    private async selectPosts(): Promise<void> {
        if (!this.config.onSelectPosts) {
            console.warn('PostsBlock: onSelectPosts callback not configured');
            return;
        }

        try {
            const selected = await this.config.onSelectPosts();
            if (selected && selected.length > 0) {
                // Filter out posts already in the block (prevent duplicates)
                const existingIds = new Set(this.data.posts.map(post => post.id));
                const newPosts = selected.filter(post => !existingIds.has(post.id));

                if (newPosts.length > 0) {
                    this.data.posts = [...this.data.posts, ...newPosts];
                    if (this.data.posts.length > 1) {
                        this.data.layout = 'list';
                    }
                    this.renderPosts();
                }
            }
        } catch (error) {
            console.error('PostsBlock: Failed to select posts', error);
        }
    }

    private removeItem(index: number): void {
        this.data.posts.splice(index, 1);
        if (this.data.posts.length === 0) {
            this.renderPlaceholder();
        } else {
            this.renderPosts();
        }
    }

    save(): PostsBlockData {
        return this.data;
    }

    validate(savedData: PostsBlockData): boolean {
        // A valid block must have at least one post
        return savedData.posts && savedData.posts.length > 0;
    }
}
