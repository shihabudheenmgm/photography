import AlertDialog from '@/Components/ui/alertdialog';
import { Button } from '@/Components/ui/button';
import PopupGallery from '@/Components/ui/popupGallery';
import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import Masonry from 'react-masonry-css';
import { Toaster, toast } from 'sonner';

type Gallery = {
    id: number;
    name: string;
    category: string;
    images?: string[];
    videos?: string[];
    videoLinks?: string[];
    video_links?: string[];
    token?: string;
    user_id: number;
    download_enabled: boolean;
    cover_image: string;
    cover_image_position?: number;
};

type MediaItem = {
    type: 'image' | 'video';
    src: string;
};

type SingleGalleryProps = {
    media: MediaItem[];
    gallery: Gallery;
    token: string;
    onBackClick: () => void;
    success?: string;
    shareLink?: string | null;
    download_enabled?: boolean;
};

function SingleGallery({
    gallery,
    success,
    token,
    onBackClick,
    shareLink: initialLink,
    download_enabled: initialDownloadEnabled,
}: SingleGalleryProps) {
    const [activeTab, setActiveTab] = useState<'images' | 'videos' | 'videoLinks'>('images');
    const [images, setImages] = useState(gallery.images || []);
    const [videos, setVideos] = useState(gallery.videos || []);
    const [videoLinks, setVideoLinks] = useState(gallery.videoLinks || gallery.video_links || []);
    const [popupOpen, setPopupOpen] = useState(false);
    const [popupIndex, setPopupIndex] = useState(0);
    const [popupMedia, setPopupMedia] = useState<MediaItem[]>([]);
    const [shareLink, setShareLink] = useState<string | null>(initialLink ?? null);
    const [isDownloadEnabled, setIsDownloadEnabled] = useState<boolean>(initialDownloadEnabled ?? false);
    const [isLoading, setIsLoading] = useState(false);
    const [coverImage, setCoverImage] = useState<string>(gallery.cover_image || '');
    
    const [imagePosition, setImagePosition] = useState(gallery.cover_image_position || 50);
    const [savedPosition, setSavedPosition] = useState(gallery.cover_image_position || 50);
    const [isEditingPosition, setIsEditingPosition] = useState(false);
    const [isSavingPosition, setIsSavingPosition] = useState(false);

    const breakpointColumnsObj = {
        default: 5,
        1100: 4,
        700: 3,
        500: 2,
    };

    const handleMediaClick = (type: 'image' | 'video', index: number) => {
        const filteredMedia: MediaItem[] =
            type === 'image' ? images.map((img) => ({ type: 'image', src: img })) : videos.map((vid) => ({ type: 'video', src: vid }));

        setPopupMedia(filteredMedia);
        setPopupIndex(index);
        setPopupOpen(true);
    };

    // Handle cover image selection
    const handleSetCoverImage = async (imageSrc: string) => {
        try {
            await router.post(
                `/galleries/${gallery.id}/set-cover`,
                { cover_image: imageSrc },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        setCoverImage(imageSrc);
                    },
                },
            );
            toast.success('Cover image updated');
        } catch (error) {
            toast.error('Failed to update cover image');
        }
    };

    // Save image position to database
    const saveImagePosition = async () => {
        setIsSavingPosition(true);
        try {
            await router.post(
                `/galleries/${gallery.id}/update-cover-position`,
                { 
                    cover_image_position: imagePosition 
                },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        setSavedPosition(imagePosition);
                        setIsEditingPosition(false);
                        toast.success('Cover image position saved');
                    },
                    onError: () => {
                        toast.error('Failed to save position');
                    }
                },
            );
        } catch (error) {
            toast.error('Failed to save position');
        } finally {
            setIsSavingPosition(false);
        }
    };

    // Toggle edit mode
    const toggleEditMode = () => {
        if (isEditingPosition) {
            // Cancel editing - reset to saved position
            setImagePosition(savedPosition);
        }
        setIsEditingPosition(!isEditingPosition);
    };

    //Delete Media
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [mediaToDelete, setMediaToDelete] = useState<{
        type: 'image' | 'video' | 'video_link';
        path: string;
    } | null>(null);

    // Handle the delete confirmation
    const handleConfirmDelete = async () => {
        if (!mediaToDelete) return;

        router.post(
            `/galleries/${gallery.id}/delete-media`,
            {
                media_type: mediaToDelete.type,
                media_path: mediaToDelete.path,
                _method: 'delete',
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    if (mediaToDelete.type === 'image' && mediaToDelete.path === coverImage) {
                        setCoverImage('');
                    }

                    if (mediaToDelete.type === 'image') {
                        setImages((prev) => prev.filter((img) => img !== mediaToDelete.path));
                    } else if (mediaToDelete.type === 'video') {
                        setVideos((prev) => prev.filter((vid) => vid !== mediaToDelete.path));
                    } else if (mediaToDelete.type === 'video_link') {
                        setVideoLinks((prev) => prev.filter((link) => link !== mediaToDelete.path));
                    }
                    setIsDeleteModalOpen(false);
                },
            },
        );
    };

    // Original function modified to use the modal
    const handleDeleteMedia = (type: 'image' | 'video' | 'video_link', path: string) => {
        setMediaToDelete({ type, path });
        setIsDeleteModalOpen(true);
    };

    //generate link
    useEffect(() => {
        const checkExistingLink = async () => {
            if (!shareLink) {
                setIsLoading(true);
                try {
                    const response = await fetch(`/galleries/${gallery.id}/shared-link`);
                    const data = await response.json();
                    if (data.shareableLink) {
                        setShareLink(data.shareableLink);
                        setIsDownloadEnabled(data.download_enabled);
                    }
                } catch (error) {
                    console.error('Error checking for existing link:', error);
                } finally {
                    setIsLoading(false);
                }
            }
        };

        checkExistingLink();
    }, [gallery.id]);

    const handleGenerateLink = async () => {
        if (shareLink) {
            await navigator.clipboard.writeText(shareLink);
            toast.success('Link copied to clipboard');
            return;
        }

        try {
            setIsLoading(true);

            await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
            const csrfToken = document.cookie
                .split('; ')
                .find((row) => row.startsWith('XSRF-TOKEN='))
                ?.split('=')[1];

            if (!csrfToken) {
                throw new Error('Failed to get CSRF token');
            }

            const res = await fetch(`/galleries/${gallery.id}/share`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': decodeURIComponent(csrfToken),
                    Accept: 'application/json',
                },
                credentials: 'include',
            });

            if (!res.ok) {
                const errorData = await res.json();
                throw new Error(errorData.message || 'Failed to generate link');
            }

            const data = await res.json();
            setShareLink(data.shareableLink);
            setIsDownloadEnabled(data.download_enabled);

            await navigator.clipboard.writeText(data.shareableLink);
            toast.success('Link generated and copied');
        } catch (err) {
            console.error('Generate link error:', err);
            toast.error('Failed to generate/copy link');
        } finally {
            setIsLoading(false);
        }
    };

    const handleEnableDownload = async () => {
        try {
            setIsLoading(true);

            await fetch('/sanctum/csrf-cookie', {
                credentials: 'include',
            });

            const csrfToken = document.cookie
                .split('; ')
                .find((row) => row.startsWith('XSRF-TOKEN='))
                ?.split('=')[1];

            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }

            const res = await fetch(`/galleries/${gallery.id}/enable-download`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': decodeURIComponent(csrfToken),
                    Accept: 'application/json',
                },
                credentials: 'include',
            });

            if (!res.ok) {
                const errorData = await res.json();
                throw new Error(errorData.message || 'Failed to enable download');
            }

            setIsDownloadEnabled(true);
            toast.success('Download enabled for shared link');
        } catch (err) {
            console.error('Enable download error:', err);
            toast.error('Failed to enable download');
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="rounded-xl bg-white p-4 md:px-8 md:py-6">
            {popupOpen && (
                <PopupGallery
                    media={popupMedia}
                    currentIndex={popupIndex}
                    onClose={() => setPopupOpen(false)}
                    onNavigate={(direction) => {
                        setPopupIndex((prev) => (direction === 'prev' ? prev - 1 : prev + 1));
                    }}
                    isAdmin={true}
                />
            )}
            {/* Delete Confirmation Modal */}
            <AlertDialog
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleConfirmDelete}
                title="Confirm Deletion"
                description="Are you sure you want to delete this file? This action cannot be undone."
                confirmText="Delete File"
                confirmColor="bg-red-600 hover:bg-red-700"
            />

            <div className="flex flex-wrap items-center justify-between border-b pb-3">
                <h3 className="text-darkclr text-base font-bold capitalize md:text-lg">{gallery.name}</h3>
                <span className="text-muted-foreground text-sm italic">{gallery.category}</span>
            </div>
            <div className="pt-6">
                <Button
                    onClick={onBackClick ?? (() => router.visit('/dashboard'))}
                    type="button"
                    variant="link"
                    className="group hover:text-bluebg mb-4 h-auto !p-0 no-underline"
                >
                    <svg height="512" viewBox="0 0 24 24" width="512" xmlns="http://www.w3.org/2000/svg" className="group-hover:fill-bluebg max-w-3">
                        <g id="Layer_2" data-name="Layer 2">
                            <path d="m21 10h-13.172l3.586-3.586a2 2 0 0 0 -2.828-2.828l-7 7a2 2 0 0 0 0 2.828l7 7a2 2 0 1 0 2.828-2.828l-3.586-3.586h13.172a2 2 0 0 0 0-4z" />
                        </g>
                    </svg>{' '}
                    <span>Back to Gallery</span>
                </Button>

                {/* Cover Image Container */}
                <div className="relative">
                    <div className={`relative flex w-full items-center justify-center overflow-hidden bg-gray-500 h-36 md:h-48 xl:h-80`}>
                        {coverImage ? (
                            <>
                                <div className={`size-full overflow-hidden`}>
                                    <img 
                                        src={coverImage} 
                                        alt="Cover Image" 
                                        className="size-full object-cover select-none transition-all duration-200 ease-out" 
                                        style={{objectPosition: `50% ${imagePosition}%`}}
                                        draggable={false}
                                    />
                                </div>
                                
                                {/* Position Controls - Absolutely positioned on image */}
                                <div className="absolute top-4 right-8 rounded-lg space-y-2 w-20 md:w-24 ">
                                    {/* Edit/Cancel Button */}
                                    <button
                                        onClick={toggleEditMode}
                                        className={`w-full px-3 py-1 text-xs rounded transition-colors cursor-pointer ${
                                            isEditingPosition 
                                                ? 'bg-orange-500 text-white hover:bg-orange-600' 
                                                : 'bg-blue-500 text-white hover:bg-blue-600'
                                        }`}
                                    >
                                        {isEditingPosition ? 'Cancel' : 'Edit Position'}
                                    </button>
                                    
                                    {/* Save Button - only visible when editing */}
                                    {isEditingPosition && (
                                        <button
                                            onClick={saveImagePosition}
                                            disabled={isSavingPosition || imagePosition === savedPosition}
                                            className="w-full px-3 py-1 bg-green-500 text-white text-xs rounded cursor-pointer hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                        >
                                            {isSavingPosition ? 'Saving...' : 'Save'}
                                        </button>
                                    )}
                                </div>
                                {isEditingPosition && (
                                    <div className='absolute top-0 right-2 bottom-0 py-2'>                                 
                                            <input
                                                type="range"
                                                min="0"
                                                max="100"
                                                value={imagePosition}
                                                onChange={(e) => setImagePosition(Number(e.target.value))}
                                                className="flex-1 w-2 h-full bg-white rounded-lg appearance-none cursor-pointer slider [writing-mode:vertical-rl] [appearance: slider-vertical]"
                                                style={{
                                                    background: `linear-gradient(to bottom, #3b82f6 0%, #3b82f6 ${imagePosition}%, #d1d5db ${imagePosition}%, #d1d5db 100%)`
                                                }}
                                            />
                                    </div>
                                )}
                            </>
                        ) : (
                            <p className="text-center text-base text-white">Gallery Cover</p>
                        )}
                    </div>
                </div>

                {success && <div className="mb-4 text-center text-sm font-medium text-green-600">{success}</div>}

                {/* Tabs */}
                <div className="tabs mt-4 flex gap-6">
                    {['images', 'videos', 'videoLinks'].map((tab) => (
                        <button
                            key={tab}
                            type="button"
                            onClick={() => setActiveTab(tab as typeof activeTab)}
                            className={`border-b-bluebg cursor-pointer text-base font-bold ${
                                activeTab === tab ? 'text-bluebg border-b-2' : 'text-darkclr border-b-0'
                            }`}
                        >
                            {tab.charAt(0).toUpperCase() + tab.slice(1)}
                        </button>
                    ))}
                </div>

                <div className="py-6">
                    {activeTab === 'images' &&
                        (images.length > 0 ? (
                            <Masonry breakpointCols={breakpointColumnsObj} className="-mx-1 flex flex-wrap items-start">
                                {images.map((image, idx) => (
                                    <div className="w-full p-1" key={idx}>
                                        <div className="group relative">
                                            <div className="absolute top-2 right-2 z-20 flex items-center gap-2">
                                                <span
                                                    className="flex size-5 items-center justify-center rounded-full transition-all group-hover:opacity-100 hover:scale-110 lg:opacity-0"
                                                    onClick={() => handleDeleteMedia('image', image)}
                                                >
                                                    <img src="/images/delete.svg" alt="Delete" className="block size-full" />
                                                </span>
                                                <span
                                                    className="flex size-5 cursor-pointer items-center justify-center rounded-full transition-all group-hover:opacity-100 hover:scale-110 lg:opacity-0"
                                                    title={coverImage === image ? 'Current Cover Image' : 'Set as Cover Image'}
                                                    onClick={() => handleSetCoverImage(image)}
                                                >
                                                    <img
                                                        src={coverImage === image ? '/images/checked.svg' : '/images/cover.svg'}
                                                        alt={coverImage === image ? 'Current Cover' : 'Set Cover'}
                                                        className="block size-full"
                                                    />
                                                </span>
                                            </div>
                                            <div
                                                className="hover:before:bg-darkclr/50 relative before:absolute before:inset-0 before:transition-all hover:cursor-pointer"
                                                onClick={() => handleMediaClick('image', idx)}
                                            >
                                                <img src={image} alt={`Gallery Image ${idx + 1}`} className="block w-full cursor-pointer shadow" />
                                                <span className="absolute inset-0 z-10 m-auto size-7 cursor-pointer transition-opacity group-hover:opacity-100 lg:opacity-0">
                                                    <svg
                                                        id="Layer_1"
                                                        height="512"
                                                        viewBox="0 0 32 32"
                                                        width="512"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        className="size-full transition-all"
                                                    >
                                                        <path
                                                            d="m12 3h-6a3 3 0 0 0 -3 3v6a1 1 0 0 0 2 0v-6a1 1 0 0 1 1-1h6a1 1 0 0 0 0-2z"
                                                            className="fill-white transition-all group-hover:-translate-0.5"
                                                        />
                                                        <path
                                                            d="m26 3h-6a1 1 0 0 0 0 2h6a1 1 0 0 1 1 1v6a1 1 0 0 0 2 0v-6a3 3 0 0 0 -3-3z"
                                                            className="fill-white transition-all group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                                        />
                                                        <path
                                                            d="m28 19a1 1 0 0 0 -1 1v6a1 1 0 0 1 -1 1h-6a1 1 0 0 0 0 2h6a3 3 0 0 0 3-3v-6a1 1 0 0 0 -1-1z"
                                                            className="fill-white transition-all group-hover:translate-0.5"
                                                        />
                                                        <path
                                                            d="m12 27h-6a1 1 0 0 1 -1-1v-6a1 1 0 0 0 -2 0v6a3 3 0 0 0 3 3h6a1 1 0 0 0 0-2z"
                                                            className="fill-white transition-all group-hover:-translate-x-0.5 group-hover:translate-y-0.5"
                                                        />
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </Masonry>
                        ) : (
                            <p className="text-muted-foreground col-span-3 m-auto text-center">No images uploaded.</p>
                        ))}

                    {activeTab === 'videos' && (
                        <div className="flex flex-wrap">
                            {videos.length > 0 ? (
                                <Masonry breakpointCols={breakpointColumnsObj} className="-mx-1 flex flex-auto flex-wrap">
                                    {videos.map((video, idx) => (
                                        <div className="w-full p-1" key={idx}>
                                            <div className="group relative">
                                                <span
                                                    className="absolute top-2 right-2 z-20 flex size-6 items-center justify-center rounded-full transition-opacity group-hover:opacity-100 lg:opacity-0"
                                                    onClick={() => handleDeleteMedia('video', video)}
                                                >
                                                    <img src="/images/delete.svg" alt="Delete" />
                                                </span>
                                                <div
                                                    className="hover:before:bg-darkclr/50 relative before:absolute before:inset-0 before:transition-all hover:cursor-pointer"
                                                    onClick={() => handleMediaClick('video', idx)}
                                                >
                                                    <video src={video} controls={false} className="cursor-pointer rounded-none shadow-md" />
                                                    <span className="absolute inset-0 z-10 m-auto size-7 cursor-pointer transition-opacity group-hover:opacity-100 lg:opacity-0">
                                                        <img src="/images/play-button.svg" alt="video" />
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </Masonry>
                            ) : (
                                <p className="text-muted-foreground col-span-3 m-auto text-center">No videos uploaded.</p>
                            )}
                        </div>
                    )}

                    {activeTab === 'videoLinks' && (
                        <div className="flex items-start gap-4">
                            {videoLinks && videoLinks.length > 0 ? (
                                videoLinks.map((link, idx) => {
                                    // Function to extract YouTube ID
                                    const getYouTubeId = (url: string) => {
                                        const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
                                        const match = url.match(regExp);
                                        return match && match[2].length === 11 ? match[2] : null;
                                    };

                                    // Function to extract Vimeo ID
                                    const getVimeoId = (url: string) => {
                                        const regExp = /^.*(vimeo\.com\/)((channels\/[A-z]+\/)|(groups\/[A-z]+\/videos\/))?([0-9]+)/;
                                        const match = url.match(regExp);
                                        return match ? match[5] : null;
                                    };

                                    const getTikTokId = (url: string) => {
                                        const match = url.match(/(?:https?:\/\/)?(?:www\.|vm\.|vt\.)?tiktok\.com\/(?:.*\/)?(\w+)(?:\?.*)?$/);
                                        return match ? match[1] : null;
                                    };

                                    const getFacebookId = (url: string) => {
                                        const match = url.match(
                                            /(?:https?:\/\/)?(?:www\.|m\.)?facebook\.com\/(?:[^\/]+\/videos\/|video\.php\?v=)(\d+)/,
                                        );
                                        return match ? match[1] : null;
                                    };

                                    const getInstagramId = (url: string) => {
                                        const match = url.match(/(?:https?:\/\/)?(?:www\.)?instagram\.com\/(?:p|reel)\/([^\/\?\&]+)/);
                                        return match ? match[1] : null;
                                    };

                                    // Determine video service and get thumbnail
                                    const renderThumbnail = (url: string) => {
                                        const youTubeId = getYouTubeId(url);
                                        const vimeoId = getVimeoId(url);
                                        const tikTokId = getTikTokId(url);
                                        const facebookId = getFacebookId(url);
                                        const instagramId = getInstagramId(url);

                                        if (youTubeId) {
                                            return (
                                                <img
                                                    src={`https://img.youtube.com/vi/${youTubeId}/mqdefault.jpg`}
                                                    alt="YouTube thumbnail"
                                                    className="h-20 w-full object-cover"
                                                />
                                            );
                                        } else if (vimeoId) {
                                            return (
                                                <img
                                                    src={`https://vumbnail.com/${vimeoId}.jpg`}
                                                    alt="Vimeo thumbnail"
                                                    className="h-20 w-full object-cover"
                                                />
                                            );
                                        } else if (tikTokId) {
                                            return (
                                                <img
                                                    src={`https://www.tiktok.com/oembed?url=https://www.tiktok.com/@placeholder/video/${tikTokId}`}
                                                    alt="TikTok thumbnail"
                                                    className="h-20 w-full object-cover"
                                                />
                                            );
                                        } else if (facebookId) {
                                            return (
                                                <img
                                                    src={`https://graph.facebook.com/${facebookId}/picture`}
                                                    alt="Facebook thumbnail"
                                                    className="h-20 w-full object-cover"
                                                />
                                            );
                                        } else if (instagramId) {
                                            return (
                                                <img
                                                    src={`https://www.instagram.com/p/${instagramId}/media/?size=m`}
                                                    alt="Instagram thumbnail"
                                                    className="h-20 w-full object-cover"
                                                />
                                            );
                                        } else {
                                            return (
                                                <div className="bg-darkclr flex h-20 w-full items-center justify-center">
                                                    <span className="text-sm text-gray-300">Video Preview</span>
                                                </div>
                                            );
                                        }
                                    };

                                    return (
                                        <div key={idx} className="group relative mb-4 w-40 overflow-hidden rounded-lg border">
                                            <a href={link} target="_blank" rel="noopener noreferrer" className="block">
                                                {renderThumbnail(link)}
                                                <span className="absolute inset-0 z-10 m-auto size-7 cursor-pointer transition-opacity group-hover:opacity-100 lg:opacity-0">
                                                    <img src="/images/play-button.svg" alt="video" />
                                                </span>
                                            </a>
                                            <span
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    handleDeleteMedia('video_link', link);
                                                }}
                                                className="absolute top-2 right-2 z-20 flex size-6 items-center justify-center rounded-full transition-opacity group-hover:opacity-100 lg:opacity-0"
                                            >
                                                <img src="/images/delete.svg" alt="Delete" className="size-5" />
                                            </span>
                                        </div>
                                    );
                                })
                            ) : (
                                <p className="text-muted-foreground m-auto text-center">No video links available.</p>
                            )}
                        </div>
                    )}
                </div>
            </div>

            <div className="mt-4 flex w-full gap-2.5">
                <Button type="submit" size="lg" variant="destructive" onClick={handleGenerateLink}>
                    {shareLink ? 'Copy Link' : 'Generate Link'}
                </Button>
                {shareLink && (
                    <Button
                        type="submit"
                        size="lg"
                        variant="destructive"
                        className={`bg-darkclr ${isDownloadEnabled ? '!cursor-not-allowed' : ''}`}
                        onClick={handleEnableDownload}
                        disabled={isDownloadEnabled}
                    >
                        {isDownloadEnabled ? 'Download Enabled' : 'Enable Download'}
                    </Button>
                )}
            </div>
            <Toaster />
        </div>
    );
}

export default SingleGallery;
