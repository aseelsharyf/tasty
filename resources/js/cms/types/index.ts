export interface User {
    id: number;
    uuid: string;
    name: string;
    email: string;
    username: string;
    email_verified_at?: string;
    avatar_url?: string | null;
    roles: string[];
    permissions: string[];
    created_at: string;
    updated_at: string;
}

export interface Role {
    id: number;
    name: string;
    permissions: string[];
    users_count?: number;
    created_at: string;
    updated_at: string;
}

export interface Permission {
    id: number;
    name: string;
    label: string;
}

export interface GroupedPermissions {
    [module: string]: Permission[];
}

export interface PageProps {
    auth: {
        user: User | null;
    };
    flash: {
        success?: string;
        error?: string;
    };
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

export interface Language {
    code: string;
    name: string;
    native_name: string;
    direction: 'ltr' | 'rtl';
}

export interface Category {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    description?: string | null;
    parent_id?: number | null;
    order?: number;
    posts_count?: number;
    depth?: number;
    children?: Category[];
    parent?: {
        id: number;
        name: string;
    } | null;
    translated_locales?: string[];
}

export interface CategoryTreeItem {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    description?: string | null;
    posts_count: number;
    order: number;
    children: CategoryTreeItem[];
    translated_locales?: string[];
}

export interface ParentOption {
    id: number;
    name: string;
    depth: number;
}

export interface Tag {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    posts_count?: number;
    created_at?: string;
    translated_locales?: string[];
}

export interface Author {
    id: number;
    name: string;
    avatar_url?: string | null;
}

// Dynamic custom fields - structure depends on post type configuration
export type CustomFields = Record<string, unknown> | null;

export interface Post {
    id: number;
    uuid: string;
    title: string;
    subtitle?: string;
    slug: string;
    excerpt?: string;
    content?: any;
    post_type: 'article' | 'recipe';
    status: 'draft' | 'pending' | 'published' | 'scheduled';
    workflow_status?: 'draft' | 'review' | 'copydesk' | 'approved' | 'rejected' | 'published';
    language_code?: string;
    author?: Author | null;
    categories?: Category[];
    tags?: Tag[];
    featured_image_url?: string | null;
    featured_image_thumb?: string | null;
    custom_fields?: CustomFields;
    meta_title?: string;
    meta_description?: string;
    published_at?: string | null;
    scheduled_at?: string | null;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

export interface PostCounts {
    all: number;
    draft: number;
    unpublished: number;
    copydesk: number;
    published: number;
    scheduled: number;
    trashed: number;
}

export interface PostFilters {
    status?: string;
    search?: string;
    post_type?: string;
    author?: number;
    category?: number;
    language?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    show_all?: string | boolean;
}

export interface PostTypeOption {
    value: string;
    label: string;
}

export interface Menu {
    id: number;
    uuid: string;
    name: string;
    name_translations?: Record<string, string>;
    location: string;
    description?: string | null;
    description_translations?: Record<string, string>;
    is_active: boolean;
    all_items_count?: number;
    items?: MenuItemTreeItem[];
    created_at?: string;
    translated_locales?: string[];
}

export interface MenuItem {
    id: number;
    uuid: string;
    menu_id: number;
    parent_id: number | null;
    label: string;
    label_translations?: Record<string, string>;
    type: 'custom' | 'external' | 'category' | 'post';
    url?: string | null;
    linkable_type?: string | null;
    linkable_id?: number | null;
    target: '_self' | '_blank';
    icon?: string | null;
    css_classes?: string[] | null;
    order: number;
    is_active: boolean;
}

export interface MenuItemTreeItem extends MenuItem {
    children: MenuItemTreeItem[];
}

export interface CategoryOption {
    id: number;
    name: string;
    slug: string;
}

export interface Page {
    id: number;
    uuid: string;
    language_code: string;
    language?: {
        code: string;
        name: string;
        native_name: string;
        direction?: 'ltr' | 'rtl';
    } | null;
    title: string;
    slug: string;
    content?: string | null;
    layout: 'default' | 'full-width' | 'sidebar';
    status: 'draft' | 'published';
    is_blade: boolean;
    author_id?: number | null;
    author?: Author | null;
    meta_title?: string | null;
    meta_description?: string | null;
    published_at?: string | null;
    created_at: string;
    updated_at: string;
}

export interface PageFilters {
    search?: string;
    status?: string;
    language?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
}

export interface PageCounts {
    total: number;
    published: number;
    draft: number;
}

// Page Layout Builder Types
export interface PageLayout {
    id: number;
    uuid: string;
    name: string;
    type: string;
    layoutable_type: string | null;
    layoutable_id: number | null;
    layoutable_name: string | null;
    is_default: boolean;
    is_active: boolean;
    is_published: boolean;
    sections_count?: number;
    sections?: PageSection[];
    created_at: string;
    updated_at: string;
}

export interface PageSection {
    id: number;
    uuid: string;
    type: string;
    type_label: string;
    config: PageSectionConfig;
    order: number;
    is_active: boolean;
    recommended_card: string;
    items?: PageSectionItem[];
}

export interface PageSectionConfig {
    card_type?: string;
    autoFetch?: boolean;
    loadAction?: 'recent' | 'trending' | 'byCategory' | 'byTag';
    loadParams?: Record<string, unknown>;
    featuredCount?: number;
    postsCount?: number;
    bg_color?: string;
    bg_color_class?: string;
    text_color?: string;
    accent_color?: string;
    divider_color?: string;
    title_small?: string;
    title_large?: string;
    description?: string;
    button_text?: string;
    button_url?: string;
    intro_image?: string;
    intro_image_alt?: string;
    background_image?: string;
    show_dividers?: boolean;
    pull_up?: boolean;
    alignment?: 'left' | 'center' | 'right';
}

export interface PageSectionItem {
    id: number;
    post_id: number | null;
    post: {
        id: number;
        uuid: string;
        title: string;
        slug: string;
    } | null;
    item_type: 'post' | 'product' | 'custom';
    item_data: Record<string, unknown> | null;
    order: number;
    is_featured: boolean;
}

export interface SectionType {
    value: string;
    label: string;
    recommended_card: string;
}

export interface CardType {
    value: string;
    label: string;
}

export interface LayoutCounts {
    total: number;
    published: number;
    draft: number;
    homepage: number;
    category: number;
    tag: number;
}

export interface LayoutFilters {
    type?: 'homepage' | 'category' | 'tag';
    status?: 'published' | 'draft';
}

// Layout Context for category/tag page builders
export interface LayoutContext {
    type: 'category' | 'tag';
    id: number;
    name: string;
    slug: string;
}

// Homepage Layout Builder Types
export interface HomepageSection {
    id: string;
    type: string;
    order: number;
    enabled: boolean;
    config: Record<string, unknown>;
    dataSource: HomepageSectionDataSource;
    slots: HomepageSectionSlot[];
}

export interface HomepageSectionDataSource {
    action: string;
    params: Record<string, unknown>;
}

export interface HomepageSectionSlot {
    index: number;
    mode: 'dynamic' | 'manual' | 'static';
    postId: number | null;
    productId?: number | null;
    product?: HomepageProduct;
    content?: Record<string, string>;
}

export interface HomepageProduct {
    title: string;
    description: string;
    image: string;
    imageAlt: string;
    tags: string[];
    url: string;
}

export interface HomepageConfiguration {
    sections: HomepageSection[];
    version: number;
    updatedAt: string | null;
    updatedBy: number | null;
}

export interface SectionTypeConfigField {
    type: 'text' | 'textarea' | 'number' | 'toggle' | 'select' | 'color' | 'media';
    label: string;
    default: unknown;
    options?: string[];
    placeholder?: string;
}

export interface PreviewSchemaArea {
    id: string;
    label: string;
    width?: string;
    height?: string;
    slotIndex?: number;
    showPlay?: boolean;
    scroll?: 'horizontal' | 'vertical';
    gridCols?: number;
    isBackground?: boolean;
    isOverlay?: boolean;
    children?: PreviewSchemaArea[];
}

export interface PreviewSchema {
    layout: string;
    alignmentKey?: string;
    areas: PreviewSchemaArea[];
}

export interface SectionTypeDefinition {
    type: string;
    name: string;
    description: string;
    icon: string;
    slotCount: number;
    minSlots: number;
    maxSlots: number;
    contentType: string; // 'post' or 'product'
    configSchema: Record<string, SectionTypeConfigField>;
    slotSchema: Record<string, SectionTypeConfigField>;
    slotLabels: Record<number, string>;
    supportedActions: string[];
    supportsDynamic: boolean;
    defaultConfig: Record<string, unknown>;
    defaultSlots: HomepageSectionSlot[];
    defaultDataSource: HomepageSectionDataSource;
    previewSchema: PreviewSchema;
}

export interface PostSearchResult {
    id: number;
    title: string;
    kicker?: string;
    excerpt?: string;
    image?: string;
    category?: string;
    postType: string;
    publishedAt?: string;
}

export interface ProductSearchResult {
    id: number;
    title: string;
    brand?: string;
    shortDescription?: string;
    image?: string;
    category?: string;
    price?: string;
    availability?: string;
}
