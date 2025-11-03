import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';
import { useDropzone } from 'react-dropzone';
import { toast, Toaster } from 'sonner';

type Gallery = {
    id: number;
    name: string;
    category: string;
    images?: string[];
    videos?: string[];
    videoLinks?: string[];
};

type AddGalleryProps = {
    onAddGallery: (gallery: Gallery) => void;
    onViewGalleryClick: () => void;
    initialGallery?: Gallery;
    isEditMode?: boolean;
};

function AddGallery({ onAddGallery, onViewGalleryClick, initialGallery }: AddGalleryProps) {
    const [name, setName] = useState(initialGallery?.name ?? '');
    const [category, setCategory] = useState(initialGallery?.category ?? '');
    const [images, setImages] = useState<File[]>([]);
    const [videos, setVideos] = useState<File[]>([]);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [formErrors, setFormErrors] = useState<{ [key: string]: string }>({});
    const [successMessage, setSuccessMessage] = useState('');
    const [previewImages, setPreviewImages] = useState<string[]>(initialGallery?.images || []);
    const [previewVideos, setPreviewVideos] = useState<string[]>(initialGallery?.videos || []);
    const [deletedImages, setDeletedImages] = useState<string[]>([]);
    const [deletedVideos, setDeletedVideos] = useState<string[]>([]);
    const [videoLinks, setVideoLinks] = useState(initialGallery?.videoLinks?.join(', ') ?? '');
    const [uploadProgress, setUploadProgress] = useState(0);
    const [totalSize, setTotalSize] = useState(0);
    const [uploadedSize, setUploadedSize] = useState(0);

    useEffect(() => {
        const calculateSize = () => {
            const imagesSize = images.reduce((sum, file) => sum + file.size, 0);
            const videosSize = videos.reduce((sum, file) => sum + file.size, 0);
            setTotalSize(imagesSize + videosSize);
        };
        calculateSize();
    }, [images, videos]);

    useEffect(() => {
        setPreviewImages(initialGallery?.images || []);
        setPreviewVideos(initialGallery?.videos || []);
    }, [initialGallery]);

    const onDropImages = (acceptedFiles: File[]) => {
        const newImageCount = images.length + acceptedFiles.length;
        if (newImageCount > 40) {
            toast.error('Maximum 40 images allowed');
            return;
        }

        const validImages = acceptedFiles.filter((file) => {
            const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            const isValidType = validTypes.includes(file.type);
            const isValidSize = file.size <= 30 * 1024 * 1024; // 30MB
            return isValidType && isValidSize;
        });

        if (validImages.length !== acceptedFiles.length) {
            toast.warning(`Some images were invalid (must be image and ≤30MB)`);
        }
        setImages((prev) => [...prev, ...validImages]);
    };

    const onDropVideos = (acceptedFiles: File[]) => {
        // Check video count limit
        const newVideoCount = videos.length + acceptedFiles.length;
        if (newVideoCount > 40) {
            toast.error('Maximum 40 videos allowed');
            return;
        }

        // Filter valid videos (type and size)
        const validVideos = acceptedFiles.filter((file) => {
            const isValidType = file.type.startsWith('video/');
            const isValidSize = file.size <= 200 * 1024 * 1024; // 200MB
            return isValidType && isValidSize;
        });

        // Show warning if some files were invalid
        if (validVideos.length !== acceptedFiles.length) {
            toast.warning(`Some videos were invalid (must be video and ≤200MB)`);
        }

        setVideos((prev) => [...prev, ...validVideos]);
    };

    const { getRootProps: getImageRootProps, getInputProps: getImageInputProps } = useDropzone({
        onDrop: onDropImages,
        multiple: true,
        accept: { 'image/*': [] },
    });

    const { getRootProps: getVideoRootProps, getInputProps: getVideoInputProps } = useDropzone({
        onDrop: onDropVideos,
        multiple: true,
        accept: { 'video/*': [] },
    });

    const handleDeleteFile = (file: File | { name: string }, type: 'image' | 'video') => {
        const name = typeof file === 'string' ? file : file.name;

        if (type === 'image') {
            // Check if it's a preview image (existing from server)
            if (previewImages.some((img) => img.includes(name.split('/').pop() || ''))) {
                setDeletedImages((prev) => [...prev, name]);
            }
            setPreviewImages((prev) => prev.filter((img) => !img.includes(name.split('/').pop() || '')));
            setImages((prev) => prev.filter((img) => img.name !== name));
        } else {
            // Similar logic for videos
            if (previewVideos.some((vid) => vid.includes(name.split('/').pop() || ''))) {
                setDeletedVideos((prev) => [...prev, name]);
            }
            setPreviewVideos((prev) => prev.filter((vid) => !vid.includes(name.split('/').pop() || '')));
            setVideos((prev) => prev.filter((vid) => vid.name !== name));
        }
    };

    const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setFormErrors({});
        setSuccessMessage('');
        setUploadProgress(0);
        setUploadedSize(0);

        // Client-side validation
        const errors: { [key: string]: string } = {};
        if (!name.trim()) errors.name = 'Name is required.';
        if (!category.trim()) errors.category = 'Category is required.';

        // Check for at least one image (either new or existing)
        const totalImages = images.length + previewImages.length - deletedImages.length;
        if (totalImages <= 0) {
            errors.images = 'At least one image is required.';
        }

        // Check individual file sizes
        const oversizedImages = images.filter((img) => img.size > 30 * 1024 * 1024);
        const oversizedVideos = videos.filter((vid) => vid.size > 200 * 1024 * 1024);

        if (oversizedImages.length > 0) {
            errors.images = `Some images exceed 30MB limit (${oversizedImages.length} files)`;
        }

        if (oversizedVideos.length > 0) {
            errors.videos = `Some videos exceed 200MB limit (${oversizedVideos.length} files)`;
        }

        if (Object.keys(errors).length > 0) {
            setFormErrors(errors);
            return;
        }

        setIsSubmitting(true);
        const formData = new FormData();
        formData.append('name', name);
        formData.append('category', category);
        formData.append('videoLinks', videoLinks.trim() || '');

        // Add files with progress tracking
        images.forEach((image) => formData.append('images[]', image));
        videos.forEach((video) => formData.append('videos[]', video));

        // Add deletions if in edit mode
        if (initialGallery) {
            if (deletedImages.length > 0) {
                formData.append('deleted_images', JSON.stringify(deletedImages));
            }
            if (deletedVideos.length > 0) {
                formData.append('deleted_videos', JSON.stringify(deletedVideos));
            }
            formData.append('_method', 'put');
        }

        try {
            const url = initialGallery ? `/galleries/${initialGallery.id}` : '/galleries';
            const response = await axios.post(url, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                onUploadProgress: (progressEvent) => {
                    if (progressEvent.total) {
                        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        setUploadProgress(percentCompleted);
                        setUploadedSize(progressEvent.loaded);
                    }
                },
            });

            // Success handling
            setUploadProgress(0);
            toast.success(initialGallery ? 'Gallery updated successfully!' : 'Gallery created successfully!');

            // Reset state
            if (!initialGallery) {
                setName('');
                setCategory('');
                setImages([]);
                setVideos([]);
                setVideoLinks('');
                setPreviewImages([]);
                setPreviewVideos([]);
            }
            setDeletedImages([]);
            setDeletedVideos([]);

            // Redirect after a brief delay
            setTimeout(() => router.get('/galleries'), 1500);
        } catch (error: any) {
            setUploadProgress(0);

            // Handle 422 validation errors
            if (error.response?.status === 422) {
                const serverErrors = error.response.data.errors || {};
                setFormErrors(serverErrors);

                // Show toast for first error
                const firstError = Object.values(serverErrors)[0];
                if (firstError) {
                    toast.error(Array.isArray(firstError) ? firstError[0] : firstError);
                }
            }
            // Handle other errors
            else {
                const errorMsg = error.response?.data?.message || 'Upload failed. Please try again.';
                setFormErrors({ general: errorMsg });
                toast.error(errorMsg);
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    // const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    //     e.preventDefault();
    //     setFormErrors({});
    //     setSuccessMessage('');
    //     setUploadProgress(0);
    //     setUploadedSize(0);

    //     const errors: { [key: string]: string } = {};
    //     if (!name.trim()) errors.name = 'Name is required.';
    //     if (!category.trim()) errors.category = 'Category is required.';

    //     const totalImages = images.length + previewImages.length;
    //     if (!initialGallery && images.length === 0) {
    //         errors.images = 'At least one image is required.';
    //     } else if (initialGallery && totalImages === 0) {
    //         errors.images = 'At least one image is required.';
    //     }

    //     if (Object.keys(errors).length > 0) {
    //         setFormErrors(errors);
    //         return;
    //     }

    //     setIsSubmitting(true);
    //     const formData = new FormData();
    //     formData.append('name', name);
    //     formData.append('category', category);
    //     formData.append('videoLinks', videoLinks.trim() || '');

    //     images.forEach((image) => formData.append('images[]', image));
    //     videos.forEach((video) => formData.append('videos[]', video));

    //     if (deletedImages.length > 0) {
    //         formData.append('deleted_images', JSON.stringify(deletedImages));
    //     }
    //     if (deletedVideos.length > 0) {
    //         formData.append('deleted_videos', JSON.stringify(deletedVideos));
    //     }

    //     const url = initialGallery ? `/galleries/${initialGallery.id}` : '/galleries';
    //     if (initialGallery) {
    //         formData.append('_method', 'put');
    //     }

    //     try {
    //         const response = await axios.post(url, formData, {
    //             headers: {
    //                 'Content-Type': 'multipart/form-data',
    //                 'X-Requested-With': 'XMLHttpRequest',
    //             },
    //             onUploadProgress: (progressEvent) => {
    //                 if (progressEvent.total) {
    //                     const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
    //                     setUploadProgress(percentCompleted);
    //                     setUploadedSize(progressEvent.loaded);
    //                 }
    //             },
    //         });

    //         setUploadProgress(0);
    //         setSuccessMessage(initialGallery ? 'Gallery updated successfully!' : 'Gallery created successfully!');
    //         setTimeout(() => setSuccessMessage(''), 3000);

    //         // Reset state after successful submission
    //         if (!initialGallery) {
    //             setName('');
    //             setCategory('');
    //             setImages([]);
    //             setVideos([]);
    //             setVideoLinks('');
    //             setPreviewImages([]);
    //             setPreviewVideos([]);
    //         }
    //         setDeletedImages([]);
    //         setDeletedVideos([]);
    //         setIsSubmitting(false);
    //         // Use router.get to redirect after success
    //         router.get('/galleries');
    //     } catch (error: any) {
    //         setUploadProgress(0);
    //         setIsSubmitting(false);
    //         if (error.response && error.response.data && error.response.data.errors) {
    //             setFormErrors(error.response.data.errors);
    //         } else if (error.response && error.response.data && error.response.data.message) {
    //             setFormErrors({ general: error.response.data.message });
    //         } else {
    //             setFormErrors({ general: 'Upload failed.' });
    //         }
    //     }
    // };

    // const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    //     e.preventDefault();
    //     setFormErrors({});
    //     setSuccessMessage('');
    //     setUploadProgress(0);
    //     setUploadedSize(0);

    //     const errors: { [key: string]: string } = {};
    //     if (!name.trim()) errors.name = 'Name is required.';
    //     if (!category.trim()) errors.category = 'Category is required.';

    //     // Validate only if creating a new gallery or no images at all
    //     const totalImages = images.length + previewImages.length;
    //     if (!initialGallery && images.length === 0) {
    //         errors.images = 'At least one image is required.';
    //     } else if (initialGallery && totalImages === 0) {
    //         errors.images = 'At least one image is required.';
    //     }

    //     if (Object.keys(errors).length > 0) {
    //         setFormErrors(errors);
    //         return;
    //     }

    //     setIsSubmitting(true);
    //     const formData = new FormData();
    //     formData.append('name', name);
    //     formData.append('category', category);
    //     formData.append('videoLinks', videoLinks.trim() || '');

    //     images.forEach((image) => formData.append('images[]', image));
    //     videos.forEach((video) => formData.append('videos[]', video));

    //     if (deletedImages.length > 0) {
    //         formData.append('deleted_images', JSON.stringify(deletedImages));
    //     }
    //     if (deletedVideos.length > 0) {
    //         formData.append('deleted_videos', JSON.stringify(deletedVideos));
    //     }

    //     const url = initialGallery ? `/galleries/${initialGallery.id}` : '/galleries';
    //     if (initialGallery) {
    //         formData.append('_method', 'put');
    //     }

    //     router.post(url, formData, {
    //         forceFormData: true,
    //         onProgress: (progress) => {
    //             if (typeof progress === 'number') {
    //                 setUploadProgress(Math.round(progress));
    //             }
    //         },
    //         onSuccess: () => {
    //             setUploadProgress(0);
    //             setSuccessMessage(initialGallery ? 'Gallery updated successfully!' : 'Gallery created successfully!');
    //             setTimeout(() => setSuccessMessage(''), 3000);

    //             // Reset state after successful submission
    //             if (!initialGallery) {
    //                 setName('');
    //                 setCategory('');
    //                 setImages([]);
    //                 setVideos([]);
    //                 setVideoLinks('');
    //                 setPreviewImages([]);
    //                 setPreviewVideos([]);
    //             }
    //             setDeletedImages([]);
    //             setDeletedVideos([]);
    //             setIsSubmitting(false);
    //             router.get('/galleries');
    //         },
    //         onError: (errors) => {
    //             setUploadProgress(0);
    //             setIsSubmitting(false);
    //             if (errors?.message) {
    //                 setFormErrors({ general: errors.message });
    //             } else {
    //                 setFormErrors(errors);
    //             }
    //         },
    //     });
    // };

    return (
        <div className="rounded-xl bg-white p-4 md:px-8 md:py-6">
            <div className="flex flex-wrap items-center justify-between border-b pb-3">
                <h3 className="text-darkclr text-base font-bold md:text-lg">{initialGallery ? 'Edit Gallery' : 'Add Gallery'}</h3>
                <Button type="button" variant="destructive" className="rounded-full" onClick={onViewGalleryClick}>
                    View Gallery
                </Button>
            </div>

            {successMessage && <div className="my-4 rounded bg-green-100 px-4 py-2 text-sm text-green-700">{successMessage}</div>}
            {formErrors.general && <div className="my-4 rounded bg-red-100 px-4 py-2 text-sm text-red-700">{formErrors.general}</div>}
            <Toaster position="top-center" richColors />

            <form onSubmit={handleSubmit} className="-mx-4 flex flex-wrap pt-8">
                <div className="mb-7 w-full px-4 lg:w-1/2">
                    <label className="text-darkclr mb-1 block text-base">Name</label>
                    <Input type="text" value={name} onChange={(e) => setName(e.target.value)} placeholder="Name" />
                    {formErrors.name && <p className="mt-1 text-xs text-red-500">{formErrors.name}</p>}
                </div>

                <div className="mb-7 w-full px-4 lg:w-1/2">
                    <label className="text-darkclr mb-1 block text-base">Category</label>
                    <Input type="text" value={category} onChange={(e) => setCategory(e.target.value)} placeholder="eg: Wedding" />
                    {formErrors.category && <p className="mt-1 text-xs text-red-500">{formErrors.category}</p>}
                </div>

                <div className="mb-7 w-full px-4 lg:w-1/2">
                    <label className="text-darkclr mb-1 block text-base">Upload Images</label>
                    <div {...getImageRootProps()} className="border-darkclr bg-lightbg cursor-pointer rounded-md border px-4 py-6 text-center">
                        <input {...getImageInputProps()} />
                        <div className="flex flex-col items-center justify-center gap-1">
                            <img src="/images/cloud.png" alt="Upload" className="max-h-10 max-w-32 opacity-50" />
                            <span className="text-sm">Drag & Drop Images</span>
                            <span className="text-sm font-bold">OR</span>
                            <span className="bg-bluebg rounded-2xl px-4 py-1 text-xs text-white">SELECT FILE</span>
                        </div>
                    </div>
                    <div>
                        Images: {images.length + previewImages.length}/40
                        {images.length + previewImages.length >= 40 && <span className="ml-2 text-red-500">(Max reached)</span>}
                    </div>
                    {formErrors.images && <p className="mt-1 text-xs text-red-500">{formErrors.images}</p>}
                    {images.length > 0 && (
                        <div className="-mx-2 mt-2 flex flex-wrap">
                            {images.map((image, idx) => (
                                <div key={idx} className="relative mb-4 w-20 px-2">
                                    <img src={URL.createObjectURL(image)} alt={`Preview ${idx}`} className="w-full" />
                                    <span
                                        onClick={() => handleDeleteFile(image, 'image')}
                                        className="absolute top-1 right-1 flex size-5 cursor-pointer"
                                    >
                                        <img src="/images/delete.svg" alt="Delete" />
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                    {previewImages.length > 0 && (
                        <div className="-mx-2 mt-2 flex flex-wrap">
                            {previewImages.map((image, idx) => (
                                <div key={idx} className="relative mb-4 w-20 px-2">
                                    <img src={image} alt={`Existing ${idx}`} className="w-full" />
                                    <span
                                        onClick={() => handleDeleteFile({ name: image }, 'image')}
                                        className="absolute top-1 right-1 flex size-5 cursor-pointer"
                                    >
                                        <img src="/images/delete.svg" alt="Delete" />
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="mb-7 w-full px-4 lg:w-1/2">
                    <label className="text-darkclr mb-1 block text-base">Upload Videos</label>
                    <div {...getVideoRootProps()} className="border-darkclr bg-lightbg cursor-pointer rounded-md border px-4 py-6 text-center">
                        <input {...getVideoInputProps()} />
                        <div className="flex flex-col items-center justify-center gap-1">
                            <img src="/images/cloud.png" alt="Upload" className="max-h-10 max-w-32 opacity-50" />
                            <span className="text-sm">Drag & Drop Videos</span>
                            <span className="text-sm font-bold">OR</span>
                            <span className="bg-bluebg rounded-2xl px-4 py-1 text-xs text-white">SELECT FILE</span>
                        </div>
                    </div>
                    <div>
                        Videos: {videos.length + previewVideos.length}/40
                        {videos.length + previewVideos.length >= 40 && <span className="ml-2 text-red-500">(Max reached)</span>}
                    </div>
                    {videos.length > 0 && (
                        <div className="-mx-2 mt-2 flex flex-wrap">
                            {videos.map((video, idx) => (
                                <div key={idx} className="relative mb-4 w-20 px-2">
                                    <video src={URL.createObjectURL(video)} controls className="w-full" />
                                    <span
                                        onClick={() => handleDeleteFile(video, 'video')}
                                        className="absolute top-1 right-1 flex size-5 cursor-pointer"
                                    >
                                        <img src="/images/delete.svg" alt="Delete" />
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                    {previewVideos.length > 0 && (
                        <div className="-mx-2 mt-2 flex flex-wrap">
                            {previewVideos.map((video, idx) => (
                                <div key={idx} className="relative mb-4 w-20 px-2">
                                    <video src={video} controls className="w-full" />
                                    <span
                                        onClick={() => handleDeleteFile({ name: video }, 'video')}
                                        className="absolute top-1 right-1 flex size-5 cursor-pointer"
                                    >
                                        <img src="/images/delete.svg" alt="Delete" />
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="mb-7 w-full px-4">
                    <label className="text-darkclr mb-1 block text-base">Video Links</label>
                    <textarea
                        value={videoLinks}
                        onChange={(e) => setVideoLinks(e.target.value)}
                        placeholder="Comma separated video links"
                        className="border-darkclr placeholder:text-muted-foreground h-40 w-full rounded-md border bg-transparent p-3 text-base shadow-xs"
                    ></textarea>
                </div>

                {uploadProgress !== 0 && (
                    <div className="bg-darkclr/70 fixed inset-0 z-50 flex items-center justify-center">
                        <div className="m-auto flex w-full max-w-80 flex-col items-center justify-center">
                            <div className="h-2.5 w-full rounded-full bg-gray-200">
                                <div className="h-2.5 rounded-full bg-blue-600" style={{ width: `${uploadProgress}%` }}></div>
                            </div>
                            <p className="mt-1 text-sm text-white">
                                <span>Uploading files...</span>
                                <span>
                                    {Math.round(uploadProgress)}% ({(uploadedSize / (1024 * 1024)).toFixed(2)} MB of{' '}
                                    {(totalSize / (1024 * 1024)).toFixed(2)} MB)
                                </span>
                            </p>
                        </div>
                    </div>
                )}

                <div className="flex w-full gap-3.5 px-4">
                    <Button type="submit" size="lg" variant="destructive" disabled={isSubmitting}>
                        {isSubmitting ? 'Submitting...' : initialGallery ? 'Update Gallery' : 'Create Gallery'}
                    </Button>
                    {initialGallery && (
                        <Button type="button" size="lg" variant="outline" onClick={() => router.visit('/dashboard')}>
                            Cancel
                        </Button>
                    )}
                </div>
            </form>
        </div>
    );
}

export default AddGallery;
