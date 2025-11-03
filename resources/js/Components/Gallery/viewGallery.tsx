import AlertDialog from '@/Components/ui/alertdialog';
import { Button } from '@/Components/ui/button';
import { router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

type Gallery = {
    id: number;
    name: string;
    category: string;
    images?: string[];
    videos?: string[];
    videoLinks?: string[];
};

type ViewGalleryProps = {
    galleries?: Gallery[];
    onAddNewGalleryClick: () => void;
    onGalleryClick: (gallery: Gallery) => void;
    onEditGalleryClick: (gallery: Gallery) => void;
};

function ViewGallery({ onAddNewGalleryClick, onGalleryClick, onEditGalleryClick }: ViewGalleryProps) {
    const { props } = usePage<{ galleries: Gallery[] }>();
    const [galleries, setGalleries] = useState<Gallery[]>(props.galleries || []);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [galleryToDelete, setGalleryToDelete] = useState<Gallery | null>(null);

    useEffect(() => {
        setGalleries(props.galleries || []);
    }, [props.galleries]);

    const handleGalleryClick = (gallery: Gallery) => {
        onGalleryClick(gallery);
    };

    const handleDeleteClick = (gallery: Gallery) => {
        setGalleryToDelete(gallery);
        setIsDeleteModalOpen(true);
    };

    const handleConfirmDelete = async () => {
        if (!galleryToDelete) return;

        try {
            await router.delete(`/galleries/${galleryToDelete.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    setGalleries((prev) => prev.filter((g) => g.id !== galleryToDelete.id));
                    setIsDeleteModalOpen(false);
                },
            });
        } catch (error) {
            console.error('Error deleting gallery:', error);
            setIsDeleteModalOpen(false);
        }
    };

    return (
        <>
            {/* Delete Confirmation Modal */}
            <AlertDialog
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleConfirmDelete}
                title="Confirm Deletion"
                description="Are you sure you want to delete this gallery? This action cannot be undone."
                confirmText="Delete Gallery"
                confirmColor="bg-red-600 hover:bg-red-700"
            />
            <div className="rounded-xl bg-white p-4 md:px-8 md:py-6">
                <div className="flex items-center justify-between border-b pb-3">
                    <h3 className="text-darkclr text-base font-bold md:text-lg">Clients Gallery</h3>
                    <Button type="button" variant="destructive" className="rounded-full max-md:p-2" onClick={onAddNewGalleryClick}>
                        <span className="max-md:hidden">Add New Gallery</span>
                        <span className="size-5 md:hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" className="size-full fill-white">
                                <path d="m467 211h-166v-166c0-24.853-20.147-45-45-45s-45 20.147-45 45v166h-166c-24.853 0-45 20.147-45 45s20.147 45 45 45h166v166c0 24.853 20.147 45 45 45s45-20.147 45-45v-166h166c24.853 0 45-20.147 45-45s-20.147-45-45-45z" />
                            </svg>
                        </span>
                    </Button>
                </div>

                {/* Gallery Listing */}
                <div className="-mx-3 mt-3 flex flex-wrap">
                    {!galleries || galleries.length === 0 ? (
                        <div className="text-darkclr flex size-full items-center justify-center px-3 py-30 text-center text-base font-bold md:text-xl lg:py-60">
                            <div className="block px-4">
                                No Galleries Found. You haven’t added any galleries yet.&nbsp;
                                <span className="text-bluebg cursor-pointer" onClick={onAddNewGalleryClick}>
                                    Add Gallery
                                </span>
                                &nbsp;to get started.
                            </div>
                        </div>
                    ) : (
                        galleries.map((gallery) => (
                            <div key={gallery.id} className="mb-1 w-full p-3 md:w-1/3 xl:w-1/5">
                                <div
                                    className="group relative flex cursor-pointer flex-col transition-all hover:shadow-md"
                                    onClick={() => handleGalleryClick(gallery)}
                                >
                                    {/* Action Buttons */}
                                    <div className="absolute top-2 right-2 z-30 flex gap-1.5 transition-opacity xl:opacity-0 xl:group-hover:opacity-100">
                                        <span
                                            className="flex size-6 items-center justify-center bg-red-500 p-1.5"
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                onEditGalleryClick(gallery);
                                            }}
                                        >
                                            <img src="/images/edit.svg" className="size-full" alt="edit" />
                                        </span>
                                        <span
                                            className="flex size-6 items-center justify-center bg-red-500 p-1"
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                handleDeleteClick(gallery);
                                            }}
                                        >
                                            <img src="/images/trash.svg" className="size-full" alt="delete" />
                                        </span>
                                    </div>

                                    {/* Gallery Thumbnail */}

                                    <figure className="h-44 overflow-hidden bg-gray-600 sm:h-64 md:h-40">
                                        {gallery.images?.[0] ? (
                                            <img
                                                src={gallery.images[0]}
                                                alt="Gallery thumbnail"
                                                className="size-full object-cover transition-all duration-700 group-hover:scale-105"
                                            />
                                        ) : (
                                            <span className="flex size-full items-center justify-center text-base text-white">No images</span>
                                        )}
                                    </figure>

                                    {/* Gallery Info */}
                                    <div className="p-2 text-center">
                                        <h4 className="text-base font-semibold">{gallery.name}</h4>
                                        <p className="text-muted-foreground text-xs italic">{gallery.category}</p>
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </>
    );
}

export default ViewGallery;
