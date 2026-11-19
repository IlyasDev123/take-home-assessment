export interface User {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
}

export interface StatusBadge {
    value: string;
    label: string;
    color: 'gray' | 'blue' | 'yellow' | 'green' | 'red';
}

export interface Generation {
    id: number;
    prompt: string;
    size: { value: string; label: string };
    quality: { value: string; label: string };
    reference_images: string[];
    status: StatusBadge;
    estimated_provider_cost: string;
    actual_provider_cost: string | null;
    estimated_user_cost: string;
    actual_user_cost: string | null;
    image_url: string | null;
    failure_reason: string | null;
    completed_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface PaginatedResource<T> {
    data: T[];
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
}

export interface SharedProps {
    auth:    { user: User | null };
    credits: { balance: string | null };
    flash:   { success: string | null; error: string | null };
}
