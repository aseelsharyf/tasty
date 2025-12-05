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

export interface RecipeMeta {
    prep_time?: number;
    cook_time?: number;
    servings?: number;
    difficulty?: 'easy' | 'medium' | 'hard';
    ingredients?: string[];
    nutrition?: {
        calories?: number;
        protein?: number;
        carbs?: number;
        fat?: number;
    };
}

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
    language_code?: string;
    author?: Author | null;
    categories?: Category[];
    tags?: Tag[];
    featured_image_url?: string | null;
    featured_image_thumb?: string | null;
    recipe_meta?: RecipeMeta | null;
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
    pending: number;
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
}

export interface PostTypeOption {
    value: string;
    label: string;
}
