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
