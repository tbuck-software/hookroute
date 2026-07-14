export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    projects: Array<{ id: number; name: string; slug: string }>;
    currentProject: null | {
        id: number;
        name: string;
        slug: string;
        timezone: string;
        can_manage: boolean;
        is_owner: boolean;
    };
    flash?: { success?: string; created_source_url?: string };
};
