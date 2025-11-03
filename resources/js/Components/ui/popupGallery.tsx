import React, { useEffect, useRef, useState } from 'react';
import { applyWatermark } from '@/lib/applyWatermark';

type MediaItem = {
    type: 'image' | 'video';
    src: string;
    watermarked?: boolean;
    durationLimit?: number;
};

type PopupGalleryProps = {
    media: MediaItem[];
    currentIndex: number;
    onClose: () => void;
    onNavigate: (direction: 'prev' | 'next') => void;
    isAdmin?: boolean;
};

const PopupGallery: React.FC<PopupGalleryProps> = ({ media, currentIndex, onClose, onNavigate,  isAdmin = false }) => {
     const [displaySrc, setDisplaySrc] = useState<string | undefined>(undefined);
    const videoRef = useRef<HTMLVideoElement>(null);
    const [timeLeft, setTimeLeft] = useState<number | null>(null)

    const currentMedia = media[currentIndex];
    if (!currentMedia) return null;

    useEffect(() => {
        if (currentMedia.type === 'image' && currentMedia.watermarked) {
            applyWatermark(currentMedia.src, '/images/watermark.png')
                .then(setDisplaySrc)
                .catch(() => setDisplaySrc(currentMedia.src));
        } else {
            setDisplaySrc(currentMedia.src);
        }
    }, [currentMedia]);

    useEffect(() => {
        const video = videoRef.current;
        if (!video || isAdmin || !currentMedia.durationLimit) return;

        const handleTimeUpdate = () => {
            if (currentMedia.durationLimit && video.currentTime >= currentMedia.durationLimit) {
                video.pause();
                video.currentTime = 0;
            }
            setTimeLeft(Math.max(0, (currentMedia.durationLimit || 0) - video.currentTime));
        };

        video.addEventListener('timeupdate', handleTimeUpdate);
        return () => video.removeEventListener('timeupdate', handleTimeUpdate);
    }, [currentMedia.durationLimit, isAdmin]);

    if (!media || media.length === 0) return null;

    const [touchStartX, setTouchStartX] = useState<number | null>(null);
    const [slideDirection, setSlideDirection] = useState<'left' | 'right' | null>(null);

    const handleTouchStart = (e: React.TouchEvent) => {
        setTouchStartX(e.touches[0].clientX);
    };
    const handleNavigate = (direction: 'left' | 'right')=> {
        if (direction === 'left' && currentIndex >= media.length - 1) return;
        if (direction === 'right' && currentIndex <= 0) return;
        setSlideDirection(direction);
        setTimeout(() => {
            onNavigate(direction === 'left' ? 'next' : 'prev');
            setSlideDirection(null);
        }, 300);
    };

    const handleTouchEnd = (e: React.TouchEvent) => {
        if (touchStartX === null) return;
        const touchEndX = e.changedTouches[0].clientX;
        const diff = touchEndX - touchStartX;
        const minSwipeDistance = 50;
        if (diff > minSwipeDistance && currentIndex > 0) {
            handleNavigate('right');
        } else if (diff < -minSwipeDistance && currentIndex < media.length - 1) {
            handleNavigate('left');
        }
        setTouchStartX(null);
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80" onTouchStart={handleTouchStart} onTouchEnd={handleTouchEnd}>
            {/* Close Button */}
            <button className="absolute md:top-4 md:right-4 top-2.5 right-2.5 text-white cursor-pointer p-3 transition-all hover:scale-110" onClick={onClose}>                
                <svg viewBox="0 0 329.26933 329" xmlns="http://www.w3.org/2000/svg" className="size-4 fill-white">
                    <path d="m194.800781 164.769531 128.210938-128.214843c8.34375-8.339844 8.34375-21.824219 0-30.164063-8.339844-8.339844-21.824219-8.339844-30.164063 0l-128.214844 128.214844-128.210937-128.214844c-8.34375-8.339844-21.824219-8.339844-30.164063 0-8.34375 8.339844-8.34375 21.824219 0 30.164063l128.210938 128.214843-128.210938 128.214844c-8.34375 8.339844-8.34375 21.824219 0 30.164063 4.15625 4.160156 9.621094 6.25 15.082032 6.25 5.460937 0 10.921875-2.089844 15.082031-6.25l128.210937-128.214844 128.214844 128.214844c4.160156 4.160156 9.621094 6.25 15.082032 6.25 5.460937 0 10.921874-2.089844 15.082031-6.25 8.34375-8.339844 8.34375-21.824219 0-30.164063zm0 0"/>
                </svg>
            </button>

            {/* Media Display */}
            <div className={`max-w-3xl max-h-screen flex p-4`}>
                {currentMedia.type === 'image' ? (
                    <img src={displaySrc} alt="Gallery Item" className={`max-w-full max-h-full object-contain transition duration-700`} />
                ) : (
                    <div className="relative">
                        <video
                            ref={videoRef}
                            src={displaySrc}
                            controls
                            className="max-w-full max-h-full object-contain transition"
                            controlsList={isAdmin ? undefined : "nodownload"}
                        />
                        {!isAdmin && currentMedia.durationLimit && timeLeft !== null && (
                            <div className="absolute top-4 right-4 bg-black/70 text-white px-2 py-1 rounded text-sm">
                                {Math.ceil(timeLeft)}s remaining
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Navigation Buttons */}
            {currentIndex > 0 && (
                <button
                    className="absolute left-4 text-white bg-black size-7 flex items-center justify-center text-base cursor-pointer"
                    onClick={() => handleNavigate('right')}

                >
                    <svg height="512" viewBox="0 0 24 24" width="512" xmlns="http://www.w3.org/2000/svg" className="max-w-3 fill-white">
                        <g id="Layer_2" data-name="Layer 2">
                            <path d="m21 10h-13.172l3.586-3.586a2 2 0 0 0 -2.828-2.828l-7 7a2 2 0 0 0 0 2.828l7 7a2 2 0 1 0 2.828-2.828l-3.586-3.586h13.172a2 2 0 0 0 0-4z" />
                        </g>
                    </svg>
                </button>
            )}
            {currentIndex < media.length - 1 && (
                <button
                    className="absolute right-4 text-white bg-black size-7 flex items-center justify-center text-base cursor-pointer"
                    onClick={() => handleNavigate('left')}
                >
                    
                <svg height="512" viewBox="0 0 24 24" width="512" xmlns="http://www.w3.org/2000/svg" className="max-w-3 fill-white"><g id="Layer_2" data-name="Layer 2"><path d="m22.414 10.586-7-7a2 2 0 0 0 -2.828 2.828l3.586 3.586h-13.172a2 2 0 0 0 0 4h13.172l-3.586 3.586a2 2 0 1 0 2.828 2.828l7-7a2 2 0 0 0 0-2.828z"/></g></svg>
                </button>
            )}
        </div>
    );
};

export default PopupGallery;