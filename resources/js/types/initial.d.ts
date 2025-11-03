export type User = {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
};

export type SharedData = {
    auth: {
        user: User | null;
    };
    view?: 'login' | 'register' | 'forgot';
};
