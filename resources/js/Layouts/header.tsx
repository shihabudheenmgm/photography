import TextLink from '@/Components/ui/textlink';
import { Inertia } from '@inertiajs/inertia';
import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useIdleTimer } from 'react-idle-timer';

interface HeaderProps {
    sideOpen: boolean;
}

interface PageProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            profile_image?: string;
            logo?: string;
        };
    };
    [key: string]: any;
}

export default function Header({ sideOpen }: HeaderProps) {
    const { auth } = usePage<PageProps>().props;

    const [showNotification, setshowNotification] = useState(false);
    const [showProfile, setshowProfile] = useState(false);

    // Logout function
    const handleLogout = () => {
        Inertia.post(
            '/logout',
            {},
            {
                onSuccess: () => {
                    //Inertia.visit('/');
                    window.location.href = '/login';
                },
            },
        );
    };

    // logout after 10 minutes
    useIdleTimer({
        timeout: 1000 * 60 * 10,
        onIdle: handleLogout,
        debounce: 500,
    });

    // Helper function to get the full logo URL
    const getLogoUrl = () => {
        if (!auth.user?.logo) return '/images/logo.png'; // Default logo

        // If it's already a full URL (e.g., from cloud storage)
        if (auth.user.logo.startsWith('http')) {
            return auth.user.logo;
        }

        // If it's a local path
        return `${window.location.origin}${auth.user.logo}`;
    };

    return (
        <>
            <header className={`fixed top-0 right-0 left-0 z-50 bg-white transition-all duration-300 ${sideOpen ? 'md:ml-14' : 'md:ml-60'}`}>
                <div className="flex items-center justify-between px-6 max-md:!pl-16 sm:px-8">
                    <TextLink href={'/'} className="me-auto inline-flex py-5">
                        <img src={getLogoUrl()} alt="Company Logo" className="block max-h-10 max-w-32 object-contain" />
                    </TextLink>
                    <ul className="flex items-center gap-3">
                        <li className="relative">
                            <TextLink
                                href={'/'}
                                className={`block rounded-full p-2 ${showNotification ? 'bg-gray-100' : ''}`}
                                onClick={(e) => {
                                    e.preventDefault();
                                    setshowNotification((prev) => !prev);
                                }}
                            >
                                <svg
                                    id="Capa_1"
                                    enableBackground="new 0 0 512 512"
                                    height="512"
                                    viewBox="0 0 512 512"
                                    width="512"
                                    className="block size-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path d="m455.7 351.22-29.75-30.31c-4.16-4.23-6.45-9.83-6.45-15.76v-91.65c0-73.98-49.39-136.63-116.93-156.73.61-3.04.93-6.15.93-9.27 0-26.19-21.31-47.5-47.5-47.5s-47.5 21.31-47.5 47.5c0 3.15.31 6.25.9 9.28-67.52 20.11-116.9 82.75-116.9 156.72v91.65c0 5.93-2.29 11.53-6.45 15.76l-29.75 30.31c-16.52 16.82-21.12 40.78-11.99 62.53 9.12 21.74 29.44 35.25 53.02 35.25h87.69c4.22 35.43 34.43 63 70.98 63s66.76-27.57 70.98-63h87.69c23.58 0 43.9-13.51 53.02-35.25 9.13-21.75 4.53-45.71-11.99-62.53zm-212.2-303.72c0-6.89 5.61-12.5 12.5-12.5s12.5 5.61 12.5 12.5c0 1.08-.14 2.05-.36 2.95-4.01-.29-8.06-.45-12.14-.45s-8.13.16-12.14.45c-.22-.89-.36-1.87-.36-2.95zm12.5 429.5c-17.2 0-31.65-11.96-35.49-28h70.98c-3.84 16.04-18.29 28-35.49 28zm179.42-76.79c-1.36 3.24-6.87 13.79-20.75 13.79h-317.34c-13.88 0-19.39-10.56-20.75-13.79-1.36-3.24-5.03-14.56 4.69-24.47l29.76-30.31c10.62-10.82 16.47-25.12 16.47-40.28v-91.65c0-70.85 57.64-128.5 128.5-128.5s128.5 57.65 128.5 128.5v91.65c0 15.16 5.85 29.46 16.47 40.28l29.76 30.31c9.72 9.9 6.05 21.23 4.69 24.47z" />
                                </svg>
                                <span className="absolute top-2 right-2 size-1.5 rounded-full bg-red-600"></span>
                            </TextLink>
                            {showNotification && !showNotification && (
                                <div className="absolute top-full right-0 w-60 rounded-md bg-white shadow-md">
                                    <h5 className="rounded-t-md bg-gray-100 px-4 py-2 text-lg font-semibold text-black">Notifications</h5>
                                    <ul className="py-2">
                                        <li>
                                            <TextLink href={'/'} className="hover:bg-bluebg/5 block px-3 py-2 text-base text-gray-600 no-underline">
                                                Notifications
                                            </TextLink>
                                        </li>
                                    </ul>
                                </div>
                            )}
                        </li>
                        <li className="relative">
                            <TextLink
                                href={'/'}
                                className={`border-bluebg block size-10 overflow-hidden rounded-full border border-solid`}
                                onClick={(e) => {
                                    e.preventDefault();
                                    setshowProfile((prev) => !prev);
                                }}
                            >
                                <img
                                    src={
                                        auth?.user?.profile_image?.startsWith('http')
                                            ? auth.user.profile_image
                                            : `${window.location.origin}${auth.user.profile_image || '/images/default.jpg'}`
                                    }
                                    alt="Profile"
                                    className="block h-full w-full object-cover"
                                />
                            </TextLink>
                            {showProfile && (
                                <div className="absolute top-full right-0 w-60 rounded-md bg-white shadow-md">
                                    <ul className="py-2">
                                        <li>
                                            <TextLink
                                                href={'/'}
                                                onClick={handleLogout}
                                                className="hover:bg-bluebg/5 block px-3 py-2 text-base text-gray-600 no-underline"
                                            >
                                                Log out
                                            </TextLink>
                                        </li>
                                    </ul>
                                </div>
                            )}
                        </li>
                    </ul>
                </div>
            </header>
        </>
    );
}
