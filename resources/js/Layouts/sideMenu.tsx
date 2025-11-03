import TextLink from '@/Components/ui/textlink';
import { usePage } from '@inertiajs/react';

interface SideMenuProps {
    sideOpen: boolean;
    setSideOpen: React.Dispatch<React.SetStateAction<boolean>>;
    brandColor?: string;
    adjustedBrandColor?: string;
}

export default function SideMenu({ sideOpen, setSideOpen }: SideMenuProps) {
    const location = usePage().url;
    const isActive = (path: string) => location === path;

    return (
        <aside
            className={`bg-bluebg fixed top-0 left-0 z-50 h-full w-full transition-all duration-300 ${
                sideOpen ? 'max-w-14 max-md:-translate-x-14' : 'max-w-14 md:max-w-60'
            }`}
        >
            <button
                type="button"
                className={`mt-5 mr-2 ml-auto flex cursor-pointer flex-col gap-1.5 p-2 ${sideOpen ? 'max-md:translate-x-14' : ''}`}
                onClick={() => setSideOpen((prev) => !prev)}
            >
                <span className={`bg-whiteclr block h-0.5 w-7 transition-all ${!sideOpen ? 'translate-y-2 rotate-45' : 'max-md:bg-bluebg'}`}></span>
                <span className={`block h-0.5 w-7 transition-all ${!sideOpen ? 'bg-transparent' : 'max-md:bg-bluebg bg-whiteclr'}`}></span>
                <span className={`bg-whiteclr block h-0.5 w-7 transition-all ${!sideOpen ? '-translate-y-2 -rotate-45' : 'max-md:bg-bluebg'}`}></span>
            </button>
            <ul className="mt-10 flex flex-col">
                <li>
                    <TextLink
                        href={'/dashboard'}
                        className={`text-whiteclr flex items-center gap-4 py-2.5 text-base font-bold no-underline ${!sideOpen ? 'justify-center max-md:flex md:justify-start md:pl-12' : 'flex justify-center'} ${isActive('/dashboard') ? 'text-bluebg bg-whiteclr' : ''}`}
                    >
                        <svg
                            id="Layer_1"
                            enableBackground="new 0 0 512 512"
                            height="512"
                            className={`block size-5 ${isActive('/dashboard') ? 'fill-bluebg' : 'fill-whiteclr'}`}
                            viewBox="0 0 512 512"
                            width="512"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <g>
                                <path d="m426 495.983h-340c-25.364 0-46-20.635-46-46v-242.02c0-8.836 7.163-16 16-16s16 7.164 16 16v242.02c0 7.72 6.28 14 14 14h340c7.72 0 14-6.28 14-14v-242.02c0-8.836 7.163-16 16-16s16 7.164 16 16v242.02c0 25.364-20.635 46-46 46z" />
                                <path d="m496 263.958c-4.095 0-8.189-1.562-11.313-4.687l-198.989-198.987c-16.375-16.376-43.02-16.376-59.396 0l-198.988 198.988c-6.248 6.249-16.379 6.249-22.627 0-6.249-6.248-6.249-16.379 0-22.627l198.988-198.989c28.852-28.852 75.799-28.852 104.65 0l198.988 198.988c6.249 6.249 6.249 16.379 0 22.627-3.123 3.125-7.218 4.687-11.313 4.687z" />
                                <path d="m320 495.983h-128c-8.837 0-16-7.164-16-16v-142c0-27.57 22.43-50 50-50h60c27.57 0 50 22.43 50 50v142c0 8.836-7.163 16-16 16zm-112-32h96v-126c0-9.925-8.075-18-18-18h-60c-9.925 0-18 8.075-18 18z" />
                            </g>
                        </svg>
                        {!sideOpen && <span className={`transition-all max-md:hidden`}>Dashboard</span>}
                    </TextLink>
                </li>
                <li>
                    <TextLink
                        href={'/profile'}
                        className={`text-whiteclr flex items-center gap-4 py-2.5 text-base font-bold no-underline ${!sideOpen ? 'justify-center max-md:flex md:justify-start md:pl-12' : 'flex justify-center'} ${isActive('/profile') ? 'text-bluebg bg-whiteclr' : ''}`}
                    >
                        <svg
                            version="1.1"
                            id="Capa_1"
                            className={`block size-5 ${isActive('/profile') ? 'fill-bluebg' : 'fill-whiteclr'}`}
                            xmlns="http://www.w3.org/2000/svg"
                            xmlnsXlink="http://www.w3.org/1999/xlink"
                            x="0px"
                            y="0px"
                            viewBox="0 0 512 512"
                            xmlSpace="preserve"
                        >
                            <g>
                                <path
                                    d="M437.02,330.98c-27.883-27.882-61.071-48.523-97.281-61.018C378.521,243.251,404,198.548,404,148
                        C404,66.393,337.607,0,256,0S108,66.393,108,148c0,50.548,25.479,95.251,64.262,121.962
                        c-36.21,12.495-69.398,33.136-97.281,61.018C26.629,379.333,0,443.62,0,512h40c0-119.103,96.897-216,216-216s216,96.897,216,216
                        h40C512,443.62,485.371,379.333,437.02,330.98z M256,256c-59.551,0-108-48.448-108-108S196.449,40,256,40
                        c59.551,0,108,48.448,108,108S315.551,256,256,256z"
                                />
                            </g>
                        </svg>
                        {!sideOpen && <span className={`transition-all max-md:hidden`}>Profile</span>}
                    </TextLink>
                </li>
            </ul>
        </aside>
    );
}
