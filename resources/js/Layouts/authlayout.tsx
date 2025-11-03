import { type PropsWithChildren } from 'react';

interface AuthLayoutProps {
    name?: string;
    title?: string;
    //description?: string;
}

export default function AuthLayout({ children, title }: PropsWithChildren<AuthLayoutProps>) {
    return (
        <div className="bg-background flex min-h-[88vh] flex-col items-center gap-6 p-6 md:min-h-svh md:justify-center md:p-10">
            <div className="w-full max-w-md px-4">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <div className="space-y-2 text-center">
                            <h1 className="text-2xl font-medium uppercase">{title}</h1>
                            {/* <p className="text-muted-foreground text-center text-sm">{description}</p> */}
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
